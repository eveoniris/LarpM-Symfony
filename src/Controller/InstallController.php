<?php


namespace App\Controller;

use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Dumper;

/**
 * LarpManager\Controllers\InstallController.
 *
 * @author kevin
 */
class InstallController extends AbstractController
{
    private function loadUserTables($connection, string $dir): void
    {
        $sql = file_get_contents($dir.'mysql.sql');
        $statement = $connection->prepare($sql);
        $statement->execute();
    }

    private function loadLarpManagerTables($connection, string $dir): void
    {
        $sql = file_get_contents($dir.'create_or_update.sql');
        $statement = $connection->prepare($sql);
        $statement->execute();
    }

    private function noUserInUserTable(array $app): bool
    {
        $UsersCount = $app['User.manager']->findCount();

        return 0 == $UsersCount;
    }

    public function createOrUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        // $app['security.access_rules'] dans le bootstrap definit deja ce comportement, ce check n'est la que
        // comme double securite
        if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('User.login');
        }

        if ('POST' === $request->getMethod()) {
            $this->loadLarpManagerTables($entityManager->getConnection(), $app['db_install_path']);

            return $this->render('install/installdone.twig');
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * Fin de l'installation.
     */
    public function doneAction(Request $request,  EntityManagerInterface $entityManager)
    {
        return $this->render('install/installdone.twig');
    }

    /**
     * Création de l'utilisateur admin.
     */
    public function createUserAction(Request $request,  EntityManagerInterface $entityManager)
    {
        // preparation du formulaire
        $form = $this->createForm(InstallUserAdminForm::class)
            ->add('create', 'submit');

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $data = $form->getData();

            $name = $data['name'];
            $email = $data['email'];
            $password = $data['password'];

            $app->register(new SecurityServiceProvider());

            $User = new \App\Entity\User($email);
            $encoder = $app['security.encoder_factory']->getEncoder($User);
            $User->setPassword($encoder->encodePassword($password, $User->getSalt()));
            $User->setUsername($name);
            $User->setRoles(['ROLE_ADMIN']);
            $User->setCreationDate(new \DateTime('NOW'));
            $User->setIsEnabled(true);

            $entityManager->persist($User);
            $entityManager->flush();

            // supprimer le fichier de cache pour lancer larpmanager en mode normal
            unlink(__DIR__.'/../../../cache/maintenance.tag');

            $app->mount('/', new \LarpManager\HomepageControllerProvider());
           $this->addFlash('success', 'L\'installation c\'est déroulée avec succès.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('install/installfirstUser.twig', ['form' => $form->createView()]);
    }

    /**
     * Mise à jour de la base de données.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $default = [
            'database_host' => $app['config']['database']['host'],
            'database_name' => $app['config']['database']['dbname'],
            'database_User' => $app['config']['database']['User'],
        ];

        // preparation du formulaire
        $form = $this->createForm(\LarpManager\Form\InstallDatabaseForm::class, $default)
            ->add('create', 'submit');

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $databaseConfig = $form->getData();

            $newConfig = $app['config'];

            $newConfig['database'] = [
                'host' => $databaseConfig['database_host'],
                'dbname' => $databaseConfig['database_name'],
                'User' => $databaseConfig['database_User'],
                'password' => $databaseConfig['database_password'],
            ];

            // write the new config
            $dumper = new Dumper();
            $yaml = $dumper->dump($newConfig);
            file_put_contents(__DIR__.'/../../../config/settings.yml', $yaml);

            // reload doctrine with the new configuration
            $app->register(new DoctrineServiceProvider(), [
                'db.options' => $newConfig['database'],
            ]);

            // load doctrine tools
            $tool = new \Doctrine\ORM\Tools\SchemaTool($app['orm.em']);

            // l'opération peut prendre du temps, il faut donc régler le temps maximum d'execution
            set_time_limit(240);

            // on récupére les méta-data de toutes les tables
            $classes = $entityManager->getMetadataFactory()->getAllMetadata();

            // on met a jour la base de donnée
            $tool->updateSchema($classes);

            return $this->render('install/installdone.twig');
        }

        return $this->render('install/update.twig', ['form' => $form->createView()]);
    }

    /**
     * Affiche la page d'installation de LarpManager.
     */
    #[Route('/install', name: 'install')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        // valeur par défaut
        $default = [
            'database_host' => $app['config']['database']['host'],
            'database_name' => $app['config']['database']['dbname'],
            'database_User' => $app['config']['database']['User'],
        ];

        // preparation du formulaire
        $form = $this->createForm(\LarpManager\Form\InstallDatabaseForm::class, $default)
            ->add('create', 'submit');

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $databaseConfig = $form->getData();

            $newConfig = $app['config'];

            $newConfig['database'] = [
                'host' => $databaseConfig['database_host'],
                'dbname' => $databaseConfig['database_name'],
                'User' => $databaseConfig['database_User'],
                'password' => $databaseConfig['database_password'],
            ];

            // write the new config
            $dumper = new Dumper();
            $yaml = $dumper->dump($newConfig);
            file_put_contents(__DIR__.'/../../../config/settings.yml', $yaml);

            // reload doctrine with the new configuration
            $app->register(new DoctrineServiceProvider(), [
                'db.options' => $newConfig['database'],
            ]);

            // load doctrine tools
            $tool = new \Doctrine\ORM\Tools\SchemaTool($app['orm.em']);

            // l'opération peut prendre du temps, il faut donc régler le temps maximum d'execution
            set_time_limit(240);

            // on récupére les méta-data de toutes les tables
            $classes = $entityManager->getMetadataFactory()->getAllMetadata();

            // on créé la base de donnée
            $tool->createSchema($classes);

            // on ajoute des éléments de base
            $etat = new \App\Entity\Etat();
            $etat->setLabel('En stock');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new \App\Entity\Etat();
            $etat->setLabel('Hors stock');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new \App\Entity\Etat();
            $etat->setLabel('A fabriquer');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new \App\Entity\Etat();
            $etat->setLabel('A acheter');
            $entityManager->persist($etat);

            // Création des niveaux de compétence
            $niveau = new \App\Entity\Level();
            $niveau->setLabel('Apprenti');
            $niveau->setIndex('1');
            $entityManager->persist($niveau);

            $niveau = new \App\Entity\Level();
            $niveau->setLabel('Initié');
            $niveau->setIndex('2');
            $entityManager->persist($niveau);

            $niveau = new \App\Entity\Level();
            $niveau->setLabel('Expert');
            $niveau->setIndex('3');
            $entityManager->persist($niveau);

            $niveau = new \App\Entity\Level();
            $niveau->setLabel('Maître');
            $niveau->setIndex('4');
            $entityManager->persist($niveau);

            $niveau = new \App\Entity\Level();
            $niveau->setLabel('Secret');
            $niveau->setIndex('5');
            $entityManager->persist($niveau);

            $rarete = new \App\Entity\Rarete();
            $rarete->setLabel('Commun');
            $rarete->setValue('1');
            $entityManager->persist($rarete);

            $rarete = new \App\Entity\Rarete();
            $rarete->setLabel('Rare');
            $rarete->setValue('2');
            $entityManager->persist($rarete);

            $genre = new \App\Entity\Genre();
            $genre->setLabel('Masculin');
            $entityManager->persist($genre);

            $genre = new \App\Entity\Genre();
            $genre->setLabel('Feminin');
            $entityManager->persist($genre);

            $age = new \App\Entity\Age();
            $age->setLabel('Jeune adulte');
            $age->setEnableCreation(true);
            $entityManager->persist($age);

            $age = new \App\Entity\Age();
            $age->setLabel('Adulte');
            $age->setEnableCreation(true);
            $entityManager->persist($age);

            $age = new \App\Entity\Age();
            $age->setLabel('Mur');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            $age = new \App\Entity\Age();
            $age->setLabel('Vieux');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            $age = new \App\Entity\Age();
            $age->setLabel('Ancien');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            // Création du topic culte
            $topic = new \App\Entity\Topic();
            $topic->setKey('TOPIC_CULTE');
            $topic->setTitle('Cultes');
            $topic->setDescription('Discussion à propos des cultes');
            $entityManager->persist($topic);

            $entityManager->flush();

            // création de l'utilisateur admin
            return $this->redirectToRoute('install_create_User');
        }

        return $this->render('install/index.twig', ['form' => $form->createView()]);
    }
}

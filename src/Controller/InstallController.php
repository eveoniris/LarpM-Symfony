<?php

namespace App\Controller;

use App\Entity\Age;
use App\Entity\Etat;
use App\Entity\Genre;
use App\Entity\Level;
use App\Entity\Rarete;
use App\Entity\User;
use App\Form\InstallDatabaseForm;
use App\Form\InstallUserAdminForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Dumper;

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

    #[Route('/install/create', name: 'install.create')]
    public function createOrUpdateAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        // $app['security.access_rules'] dans le bootstrap definit deja ce comportement, ce check n'est la que
        // comme double securite
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('user.app_login');
        }

        if ('POST' === $request->getMethod()) {
            $this->loadLarpManagerTables($entityManager->getConnection(), 'docker/db/');

            return $this->render('install/installdone.twig');
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * Fin de l'installation.
     */
    #[Route('/install/done', name: 'install.done')]
    public function doneAction(): Response
    {
        return $this->render('install/installdone.twig');
    }

    /**
     * Création de l'utilisateur admin.
     */
    #[Route('/install/create-user', name: 'install.create-user')]
    public function createUserAction(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): RedirectResponse|Response {
        // preparation du formulaire
        $form = $this->createForm(InstallUserAdminForm::class)
            ->add('create', SubmitType::class);

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $data = $form->getData();

            $name = $data['name'];
            $email = $data['email'];
            $password = $data['password'];

            $user = new User($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setUsername($name);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setCreationDate(new \DateTime('NOW'));
            $user->setIsEnabled(true);

            $entityManager->persist($user);
            $entityManager->flush();

            // supprimer le fichier de cache pour lancer larpmanager en mode normal
            if (file_exists(__DIR__.'/../../../cache/maintenance.tag')) {
                unlink(__DIR__.'/../../../cache/maintenance.tag');
            }

            $this->addFlash('success', 'L\'installation c\'est déroulée avec succès.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('install/installfirstuser.twig', ['form' => $form->createView()]);
    }

    /**
     * Mise à jour de la base de données.
     */
    #[Route('/install/update', name: 'install.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $default = [
            'database_host' => '',
            'database_name' => '',
            'database_user' => '',
        ];

        // preparation du formulaire
        $form = $this->createForm(InstallDatabaseForm::class, $default)
            ->add('create', SubmitType::class);

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $databaseConfig = $form->getData();

            $newConfig['database'] = [
                'host' => $databaseConfig['database_host'],
                'dbname' => $databaseConfig['database_name'],
                'user' => $databaseConfig['database_user'],
                'password' => $databaseConfig['database_password'],
            ];

            // write the new config
            $dumper = new Dumper();
            $yaml = $dumper->dump($newConfig);
            file_put_contents(__DIR__.'/../../../config/settings.yml', $yaml);

            return $this->render('install/installdone.twig');
        }

        return $this->render('install/update.twig', ['form' => $form->createView()]);
    }

    /**
     * Affiche la page d'installation de LarpManager.
     */
    #[Route('/install', name: 'install')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        // valeur par défaut
        $default = [
            'database_host' => '',
            'database_name' => '',
            'database_user' => '',
        ];

        // preparation du formulaire
        $form = $this->createForm(InstallDatabaseForm::class, $default)
            ->add('create', SubmitType::class);

        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $databaseConfig = $form->getData();

            $newConfig['database'] = [
                'host' => $databaseConfig['database_host'],
                'dbname' => $databaseConfig['database_name'],
                'user' => $databaseConfig['database_user'],
                'password' => $databaseConfig['database_password'],
            ];

            // write the new config
            $dumper = new Dumper();
            $yaml = $dumper->dump($newConfig);
            file_put_contents(__DIR__.'/../../../config/settings.yml', $yaml);

            // on ajoute des éléments de base
            $etat = new Etat();
            $etat->setLabel('En stock');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new Etat();
            $etat->setLabel('Hors stock');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new Etat();
            $etat->setLabel('A fabriquer');
            $entityManager->persist($etat);
            $entityManager->flush();

            $etat = new Etat();
            $etat->setLabel('A acheter');
            $entityManager->persist($etat);

            // Création des niveaux de compétence
            $niveau = new Level();
            $niveau->setLabel('Apprenti');
            $niveau->setIndex('1');
            $entityManager->persist($niveau);

            $niveau = new Level();
            $niveau->setLabel('Initié');
            $niveau->setIndex('2');
            $entityManager->persist($niveau);

            $niveau = new Level();
            $niveau->setLabel('Expert');
            $niveau->setIndex('3');
            $entityManager->persist($niveau);

            $niveau = new Level();
            $niveau->setLabel('Maître');
            $niveau->setIndex('4');
            $entityManager->persist($niveau);

            $niveau = new Level();
            $niveau->setLabel('Secret');
            $niveau->setIndex('5');
            $entityManager->persist($niveau);

            $rarete = new Rarete();
            $rarete->setLabel('Commun');
            $rarete->setValue('1');
            $entityManager->persist($rarete);

            $rarete = new Rarete();
            $rarete->setLabel('Rare');
            $rarete->setValue('2');
            $entityManager->persist($rarete);

            $genre = new Genre();
            $genre->setLabel('Masculin');
            $entityManager->persist($genre);

            $genre = new Genre();
            $genre->setLabel('Feminin');
            $entityManager->persist($genre);

            $age = new Age();
            $age->setLabel('Jeune adulte');
            $age->setEnableCreation(true);
            $entityManager->persist($age);

            $age = new Age();
            $age->setLabel('Adulte');
            $age->setEnableCreation(true);
            $entityManager->persist($age);

            $age = new Age();
            $age->setLabel('Mur');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            $age = new Age();
            $age->setLabel('Vieux');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            $age = new Age();
            $age->setLabel('Ancien');
            $age->setEnableCreation(false);
            $entityManager->persist($age);

            $entityManager->flush();

            // création de l'utilisateur admin
            return $this->redirectToRoute('install_create_user');
        }

        return $this->render('install/index.twig', ['form' => $form->createView()]);
    }
}

<?php


namespace App\Form\Rumeur;

use App\Entity\Rumeur;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Groupe\RumeurForm.
 *
 * @author kevin
 */
class RumeurForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'label' => 'Le contenu de votre rumeur',
            'required' => true,
            'attr' => [
                'class' => 'tinymce',
                'row' => 9,
                'help' => 'Votre rumeur. Ce texte sera disponibles aux joueurs membres du territoire dans lequel cours la rumeur.',
            ],
        ])
            ->add('territoire', EntityType::class, [
                'label' => 'Territoire dans lequel cours la rumeur',
                'required' => false,
                'class' => \App\Entity\Territoire::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
                'choice_label' => 'nom',
                'attr' => [
                    'help' => 'Le territoire choisi donnera accès à la rumeur à tous les personnages membre de ce territoire. Remarque, si vous choisissez un territoire de type pays (ex : Aquilonnie), les territoires qui en dépendent (ex : bossonie du nord) auront aussi accès à la rumeur. Si vous ne choisissez pas de territoire, la rumeur sera accessible à tous.',
                ],
            ])
            ->add('gn', EntityType::class, [
                'label' => 'GN référant',
                'required' => true,
                'class' => \App\Entity\Gn::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('g');
                    $qb->orderBy('g.id', 'DESC');

                    return $qb;
                },
                'choice_label' => 'label',
                'attr' => [
                    'help' => 'Choisissez le GN dans lequel sera utilisé votre rumeur',
                ],
            ])
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Brouillon' => 'non_disponible',
                    'Disponible pour les joueurs' => 'disponible',
                ],
                'attr' => [
                    'La rumeur ne sera visible par les joueurs que lorsque sa visibilité sera "Disponible pour les joueurs".',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rumeur::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'rumeur';
    }
}

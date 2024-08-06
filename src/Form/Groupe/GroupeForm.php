<?php


namespace App\Form\Groupe;

use App\Entity\Groupe;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Groupe\GroupeForm.
 *
 * @author kevin
 */
class GroupeForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class)
            ->add('numero', IntegerType::class, [
                'required' => true,
            ])
            ->add('pj', ChoiceType::class, [
                'label' => 'Type de groupe',
                'required' => true,
                'choices' => [
                    'Groupe composé de PJs' => true,
                    'Groupe composé PNJs' => false,
                ],
                'expanded' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('scenariste', EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => User::class,
                'choice_label' => 'name',
                'autocomplete' => true,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->where($qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));

                    return $qb;
                },
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
            'searchable_fields' => ['username'],
            'security' => ['ROLE_ADMIN', 'ROLE_SCENARISTE', 'ROLE_ORGA'],
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('u');
                $qb->where($qb->expr()->orX(
                    $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                    $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));

                return $qb;
            },
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupe';
    }
}

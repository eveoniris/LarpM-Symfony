<?php


namespace App\Form\Groupe;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
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
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('numero', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => true,
            ])
            ->add('pj', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Type de groupe',
                'required' => true,
                'choices' => [
                    'Groupe composé de PJs' => true,
                    'Groupe composé PNJs' => false,
                ],
                'expanded' => true,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'choice_label' => 'name',
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
            'data_class' => '\\'.\App\Entity\Groupe::class,
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

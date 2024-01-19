<?php


namespace App\Form\GroupeSecondaire;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeSecondaireForm.
 *
 * @author kevin
 */
class GroupeSecondaireForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text')
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce'],
            ])
            ->add('description_secrete', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'label' => 'Description des secrets',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'les secrets ne sont accessibles qu\'aux membres selectionnés par le scénariste'],
            ])
            ->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Scénariste',
                'class' => \App\Entity\User::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->orderBy('u.nom', 'ASC');
                    $qb->orderBy('u.surnom', 'ASC');

                    return $qb;
                },
                'choice_label' => 'etatCivil',
            ])
            ->add('scenariste', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'choice_label' => 'name',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));
                    $qb->orderBy('ec.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('secondaryGroupType', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Type',
                'required' => true,
                'class' => \App\Entity\SecondaryGroupType::class,
                'choice_label' => 'label',
            ])
            ->add('secret', 'checkbox', [
                'label' => 'Cochez cette case pour rendre le groupe secret (visible uniquement par les joueurs membres)',
                'required' => false,
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\SecondaryGroup::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'secondaryGroup';
    }
}

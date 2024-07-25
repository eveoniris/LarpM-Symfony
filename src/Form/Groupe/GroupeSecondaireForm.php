<?php


namespace App\Form\Groupe;

use App\Entity\Personnage;
use App\Entity\SecondaryGroup;
use App\Entity\SecondaryGroupType;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce'],
            ])
            ->add('description_secrete', TextareaType::class, [
                'required' => true,
                'label' => 'Description des secrets',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'les secrets ne sont accessibles qu\'aux membres selectionnés par le scénariste'],
            ])
            ->add('scenariste', EntityType::class, [
                'label' => 'Scénariste',
                'required' => false,
                'class' => User::class,
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
            ->add('responsable', EntityType::class, [
                'required' => false,
                'label' => 'Chef du groupe',
                'class' => Personnage::class,
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->orderBy('u.nom', 'ASC');
                    $qb->orderBy('u.surnom', 'ASC');

                    return $qb;
                },
                'choice_label' => 'identity',
            ])
            ->add('secondaryGroupType', EntityType::class, [
                'label' => 'Type',
                'required' => true,
                'class' => SecondaryGroupType::class,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecondaryGroup::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
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
        return 'secondaryGroup';
    }
}

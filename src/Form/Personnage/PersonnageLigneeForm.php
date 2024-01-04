<?php


namespace App\Form\Personnage;

use LarpManager\Repository\LigneesRepository;
use LarpManager\Repository\PersonnageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageLigneeForm.
 *
 * @author Kevin F.
 */
class PersonnageLigneeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('parent1', 'entity', [
            'label' => 'Choisissez le Parent 1 du personnage',
            'expanded' => false,
            'required' => false,
            'class' => \App\Entity\Personnage::class,
            'choice_label' => static function ($personnage) {
                return $personnage->getIdentity();
            },
            'query_builder' => static function (PersonnageRepository $pr) {
                return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
            },
        ])
            ->add('parent2', 'entity', [
                'label' => 'Choisissez le Parent 2 du personnage',
                'expanded' => false,
                'required' => false,
                'empty_data' => null,
                'class' => \App\Entity\Personnage::class,
                'choice_label' => static function ($personnage) {
                    return $personnage->getIdentity();
                },
                'query_builder' => static function (PersonnageRepository $pr) {
                    return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
            ])
            ->add('lignee', 'entity', [
                'label' => 'Choisissez la lignée de votre personnage ',
                'expanded' => false,
                'required' => false,
                'empty_data' => null,
                'class' => \App\Entity\Lignee::class,
                'query_builder' => static function (LigneesRepository $pr) {
                    return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageLignee';
    }
}

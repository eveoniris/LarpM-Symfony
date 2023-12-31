<?php


namespace App\Form;

use Doctrine\ORM\EntityRepository;
use LarpManager\Repository\ClasseRepository;
use LarpManager\Repository\CompetenceRepository;
use LarpManager\Repository\GroupeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageFindForm.
 *
 * @author kevin
 */
class PersonnageFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'label' => 'Recherche',
            'attr' => [
                'placeholder' => 'Votre recherche',
                'aria-label' => '...',
            ],
        ])
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'id' => 'ID',
                    'nom' => 'Nom',
                ],
                'label' => 'Type',
                'attr' => [
                    'aria-label' => '...',
                ],
            ])
            ->add('religion', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => '	Par religion : ',
                'class' => \App\Entity\Religion::class,
                'property' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')->orderBy('r.label', 'ASC');
                },
            ])
            ->add('competence', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => '	Par compétence : ',
                'class' => \App\Entity\Competence::class,
                'property' => 'label',
                'query_builder' => static function (CompetenceRepository $cr) {
                    return $cr->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => '	Par classe : ',
                'class' => \App\Entity\Classe::class,
                'property' => 'label',
                'query_builder' => static function (ClasseRepository $er) {
                    return $er->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('groupe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => '	Par groupe : ',
                'class' => \App\Entity\Groupe::class,
                'property' => 'nom',
                'query_builder' => static function (GroupeRepository $gr) {
                    return $gr->createQueryBuilder('gr')->orderBy('gr.nom', 'ASC');
                },
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageFind';
    }
}

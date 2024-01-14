<?php


namespace App\Form\Trombinoscope;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\TrombinoscopeForm.
 *
 * @author kevin
 */
class TrombinoscopeForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('renomme', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'label' => 'Renommé supérieur ou égale à',
            'required' => false,
        ])
            ->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Territoire::class,
                'choice_label' => 'nomComplet',
                'label' => 'Par territoire',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere($qb->expr()->isNull('t.territoire'));
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Territoire',
                ],
            ])
            ->add('classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Classe::class,
                'choice_label' => 'label',
                'label' => 'Par classe',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\ClasseRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.label_masculin', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Classe',
                ],
            ])
            ->add('competence', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Competence::class,
                'choice_label' => 'label',
                'label' => 'Par competence',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\CompetenceRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.id', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Competence',
                ],
            ])
            ->add('religion', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Religion::class,
                'choice_label' => 'label',
                'label' => 'Par religion',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\ReligionRepository $er) {
                    $qb = $er->createQueryBuilder('r');
                    $qb->orderBy('r.label', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Religion',
                ],
            ])
            ->add('language', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Langue::class,
                'choice_label' => 'label',
                'label' => 'Par langue',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\LangueRepository $er) {
                    $qb = $er->createQueryBuilder('l');
                    $qb->orderBy('l.label', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Langue',
                ],
            ])
            ->add('groupe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'class' => \App\Entity\Groupe::class,
                'choice_label' => 'nom',
                'label' => 'Par groupe',
                'expanded' => false,
                'query_builder' => static function (\LarpManager\Repository\GroupeRepository $er) {
                    $qb = $er->createQueryBuilder('g');
                    $qb->orderBy('g.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Groupe',
                ],
            ])
            ->add('find', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Filtrer']);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'trombinoscope';
    }
}

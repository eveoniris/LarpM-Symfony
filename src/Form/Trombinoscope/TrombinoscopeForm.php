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
        $builder->add('renomme', 'integer', [
            'label' => 'Renommé supérieur ou égale à',
            'required' => false,
        ])
            ->add('territoire', 'entity', [
                'required' => false,
                'class' => \App\Entity\Territoire::class,
                'property' => 'nomComplet',
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
            ->add('classe', 'entity', [
                'required' => false,
                'class' => \App\Entity\Classe::class,
                'property' => 'label',
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
            ->add('competence', 'entity', [
                'required' => false,
                'class' => \App\Entity\Competence::class,
                'property' => 'label',
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
            ->add('religion', 'entity', [
                'required' => false,
                'class' => \App\Entity\Religion::class,
                'property' => 'label',
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
            ->add('language', 'entity', [
                'required' => false,
                'class' => \App\Entity\Langue::class,
                'property' => 'label',
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
            ->add('groupe', 'entity', [
                'required' => false,
                'class' => \App\Entity\Groupe::class,
                'property' => 'nom',
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
            ->add('find', 'submit', ['label' => 'Filtrer']);
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

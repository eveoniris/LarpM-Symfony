<?php


namespace App\Form;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Groupe;
use App\Entity\Religion;
use App\Repository\ReligionRepository;
use App\Repository\ClasseRepository;
use App\Repository\CompetenceRepository;
use App\Repository\GroupeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $builder->add('value', TextType::class, [
            'required' => false,
            'label' => 'Recherche',
            'attr' => [
                'placeholder' => 'Votre recherche',
                'aria-label' => '...',
            ],
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'ID' => 'id',
                    'Nom' => 'nom',
                ],
                'label' => 'Type',
                'attr' => [
                    'aria-label' => '...',
                ],
            ])
            ->add('religion', EntityType::class, [
                'required' => false,
                'label' => '	Par religion : ',
                'placeholder' => 'Filtrer par religion',
                'class' => Religion::class,
                'choice_label' => 'label',
                'query_builder' => static function (ReligionRepository $er) {
                    return $er->createQueryBuilder('r')->orderBy('r.label', 'ASC');
                },
            ])
            ->add('competence', EntityType::class, [
                'required' => false,
                'label' => '	Par compétence : ',
                'placeholder' => 'Filtrer par compétence',
                'class' => Competence::class,
                'choice_label' => 'label',
                'query_builder' => static function (CompetenceRepository $cr) {
                    return $cr->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('classe', EntityType::class, [
                'required' => false,
                'label' => '	Par classe : ',
                'placeholder' => 'Filtrer par classe',
                'class' => Classe::class,
                'choice_label' => 'label',
                'query_builder' => static function (ClasseRepository $er) {
                    return $er->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('groupe', EntityType::class, [
                'required' => false,
                'label' => '	Par groupe : ',
                'placeholder' => 'Filtrer par groupe',
                'class' => Groupe::class,
                'choice_label' => 'nom',
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
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageFind';
    }
}

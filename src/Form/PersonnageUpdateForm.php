<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdateForm.
 *
 * @author kevin
 */
class PersonnageUpdateForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', 'text', [
                'required' => false,
                'label' => '',
            ])
            ->add('age', 'entity', [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Age::class,
                'property' => 'label',
            ])
            ->add('genre', 'entity', [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Genre::class,
                'property' => 'label',
            ])
            ->add('territoire', 'entity', [
                'required' => true,
                'label' => 'Origine du personnage',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
            ])
            ->add('intrigue', 'choice', [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', 'choice', [
                'required' => true,
                'choices' => [false => 'Non', true => 'Oui'],
                'label' => 'Personnage sensible',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageUpdate';
    }
}

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
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => false,
                'label' => '',
            ])
            ->add('age', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Age::class,
                'choice_label' => 'label',
            ])
            ->add('genre', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Genre::class,
                'choice_label' => 'label',
            ])
            ->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'Origine du personnage',
                'class' => \App\Entity\Territoire::class,
                'choice_label' => 'nom',
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
            ])
            ->add('intrigue', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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

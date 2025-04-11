<?php


namespace App\Form;

use App\Entity\Age;
use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageUpdateForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', TextType::class, [
                'required' => false,
                'label' => '',
            ])
            ->add('age', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Age::class,
                'choice_label' => 'label',
            ])
            ->add('genre', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Genre::class,
                'choice_label' => 'label',
            ])
            ->add('territoire', EntityType::class, [
                'required' => true,
                'label' => 'Origine du personnage',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');

                    return $qb;
                },
            ])
            ->add('intrigue', ChoiceType::class, [
                'required' => true,
                'choices' => ['Oui' => true, 'Non' => false],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label' => 'Personnage sensible',
            ])
            ->add('bracelet', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label' => 'Possédez-vous votre bracelet de langue',
                'help' => 'Si vous cochez Oui. Vous ne recevrez pas de bracelet de langue dans votre enveloppe personnage ',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
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

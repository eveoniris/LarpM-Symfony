<?php


namespace App\Form\Personnage;

use App\Entity\Age;
use App\Entity\Classe;
use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use Doctrine\ORM\EntityRepository;
use App\Repository\ClasseRepository;
use App\Repository\TerritoireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PersonnageForm extends AbstractType
{
    /**
     * Construction du formulaire.
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
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('a');
                    $qb->andWhere('a.enableCreation = true');

                    return $qb;
                },
            ])
            ->add('genre', EntityType::class, [
                'required' => true,
                'label' => '',
                'class' => Genre::class,
                'choice_label' => 'label',
            ])
            ->add('classe', EntityType::class, [
                'required' => true,
                'label' => 'Votre classe',
                'class' => Classe::class,
                'choice_label' => 'label',
                'query_builder' => static function (ClasseRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->where('c.creation = true');
                    $qb->orderBy('c.label_masculin', 'ASC');

                    return $qb;
                },
            ])
            ->add('territoire', EntityType::class, [
                'required' => true,
                'label' => 'Votre origine',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');
                    $qb->orderBy('t.nom', 'ASC');

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
                'label' => 'Possédez-vous votre propre bracelet pour les langues du jeu ?',
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
        return 'personnage';
    }
}

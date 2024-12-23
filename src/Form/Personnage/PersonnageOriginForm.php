<?php


namespace App\Form\Personnage;

use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageOriginForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territoire', EntityType::class, [
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
        return 'personnageOrigin';
    }
}

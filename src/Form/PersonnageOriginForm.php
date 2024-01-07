<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageOriginForm.
 *
 * @author kevin
 */
class PersonnageOriginForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'label' => 'Votre origine',
            'class' => \App\Entity\Territoire::class,
            'property' => 'nom',
            'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
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
            'data_class' => \App\Entity\Personnage::class,
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

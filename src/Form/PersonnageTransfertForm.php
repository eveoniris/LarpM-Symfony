<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageTransfertForm.
 *
 * @author kevin
 */
class PersonnageTransfertForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('participant', 'entity', [
            'required' => true,
            'label' => 'Nouveau propriétaire',
            'class' => \App\Entity\User::class,
            'property' => 'identity',
            'mapped' => false,
            /*					'query_builder' => function(\LarpManager\Repository\TerritoireRepository $er) {
                                    $qb = $er->createQueryBuilder('t');
                                    $qb->andWhere('t.territoire IS NULL');
                                    $qb->orderBy('t.nom', 'ASC');
                                    return $qb;
                                }*/
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageTransfert';
    }
}

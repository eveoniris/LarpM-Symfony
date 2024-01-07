<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\ParticipantBilletForm.
 *
 * @author kevin
 */
class ParticipantBilletForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('billet', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez le billet a donner à cet utilisateur',
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'class' => \App\Entity\Billet::class,
            'property' => 'fullLabel',
            'query_builder' => static function ($er) {
                $qb = $er->createQueryBuilder('b');
                $qb->orderBy('b.gn', 'ASC');

                return $qb;
            },
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Participant::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'participantBillet';
    }
}

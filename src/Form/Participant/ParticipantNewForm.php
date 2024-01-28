<?php


namespace App\Form\Participant;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Participant\ParticipantNewForm.
 *
 * @author kevin
 */
class ParticipantNewForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez le jeu a affecter à cet utilisateur',
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'class' => \App\Entity\Gn::class,
            'choice_label' => 'label',
        ])
            ->add('billet', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez le billet a donner à cet utilisateur',
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'class' => \App\Entity\Billet::class,
                'choice_label' => 'fullLabel',
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
    public function configureOptions(OptionsResolver $resolver): void
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
        return 'participantNew';
    }
}

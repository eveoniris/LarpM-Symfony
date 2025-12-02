<?php


namespace App\Form\Participant;

use App\Entity\GroupeGn;
use App\Entity\Participant;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantGroupeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupeGn', EntityType::class, [
            'label' => 'Choisissez le groupe à affecter à cet utilisateur',
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'class' => GroupeGn::class,
            'choice_label' => 'groupe.nom',
            'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('gg')
                ->join('gg.groupe', 'g')
                ->join('gg.gn', 'gn')
                ->orderBy('g.nom', 'ASC')
                ->where('gn.id = :gnId')
                ->setParameter('gnId', $options['gnId']),
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Participant::class,
            'gnId' => null,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'participantGroupe';
    }
}

<?php


namespace App\Form\Participant;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Participant\ParticipantGroupeForm.
 *
 * @author kevin
 */
class ParticipantGroupeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupeGn', 'entity', [
            'label' => 'Choisissez le groupe a affecter à cet utilisateur',
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'class' => \App\Entity\GroupeGn::class,
            'property' => 'groupe.nom',
            'query_builder' => static function (EntityRepository $er) use ($options) {
                $qb = $er->createQueryBuilder('gg');
                $qb->join('gg.groupe', 'g');
                $qb->join('gg.gn', 'gn');
                $qb->orderBy('g.nom', 'ASC');
                $qb->where('gn.id = :gnId');
                $qb->setParameter('gnId', $options['gnId']);

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

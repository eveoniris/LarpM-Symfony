<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\RequestForm.
 *
 * @author kevin
 */
class RequestAllianceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('requestedGroupe', 'entity', [
            'required' => true,
            'label' => 'Groupe avec lequel vous voulez vous allier',
            'class' => \App\Entity\Groupe::class,
            'query_builder' => static function (\LarpManager\Repository\GroupeRepository $er) {
                return $er->createQueryBuilder('g')
                    ->where('g.pj = true')
                    ->orderBy('g.nom', 'ASC');
            },
            'property' => 'nom',
        ])
            ->add('message', 'textarea', [
                'label' => 'Un petit mot pour expliquer votre démarche',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                    'help' => 'Ce texte sera transmis au chef de groupe concerné.'],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeAllie::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'requestAlliance';
    }
}

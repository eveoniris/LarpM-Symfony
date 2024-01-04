<?php


namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdateLangueForm.
 *
 * @author kevin
 */
class PersonnageUpdateLangueForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('langues', 'entity', [
            'required' => true,
            'multiple' => true,
            'expanded' => true,
            'class' => \App\Entity\Langue::class,
            'choice_label' => 'label',
            'label' => 'Choisissez les langues du personnage',
            'mapped' => false,
            'query_builder' => static function (EntityRepository $repository) {
                return $repository->createQueryBuilder('l')->addOrderBy('l.secret', 'ASC')->addOrderBy('l.diffusion', 'DESC')->addOrderBy('l.label', 'ASC');
            },
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
        return 'personnageUpdateLangue';
    }
}

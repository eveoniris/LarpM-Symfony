<?php


namespace App\Form\Territoire;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Territoire\TerritoireCiblesForm.
 *
 * @author kevin
 */
class TerritoireCiblesForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territoireCibles', 'entity', [
            'required' => false,
            'label' => 'Territoire',
            'class' => \App\Entity\Territoire::class,
            'multiple' => true,
            'expanded' => true,
            'mapped' => true,
            'property' => 'nom',
            'query_builder' => static function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('t');
                $qb->where('t.territoire IS NULL');

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
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoireCibles';
    }
}

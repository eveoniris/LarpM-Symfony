<?php


namespace App\Form\Stock;

use App\Form\Type\ObjetCaracType;
use App\Form\Type\PhotoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ObjetType.
 *
 * @author kevin
 */
class ObjetForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', ['required' => true])
            ->add('numero', 'text', ['required' => true])
            ->add('description', 'textarea', ['required' => false])
            ->add('photo', new PhotoType(), ['required' => false])
            ->add('proprietaire', 'entity', ['required' => false, 'class' => \App\Entity\Proprietaire::class, 'property' => 'nom'])
            ->add('responsable', 'entity', ['required' => false, 'class' => \App\Entity\User01::class, 'property' => 'name'])
            ->add('rangement', 'entity', ['required' => false, 'class' => \App\Entity\Rangement::class, 'property' => 'adresse'])
            ->add('etat', 'entity', ['required' => false, 'class' => \App\Entity\Etat::class, 'property' => 'label'])
            ->add('tags', 'entity', ['required' => false, 'class' => \App\Entity\Tag::class, 'property' => 'nom', 'multiple' => true])
            ->add('objetCarac', new ObjetCaracType(), ['required' => false])
            ->add('cout', 'integer', ['required' => false])
            ->add('nombre', 'integer', ['required' => false])
            ->add('budget', 'integer', ['required' => false])
            ->add('investissement', 'choice', ['choices' => ['true' => 'ré-utilisable', 'false' => 'usage unique']]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objet';
    }
}

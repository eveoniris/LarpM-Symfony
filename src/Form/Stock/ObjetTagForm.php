<?php


namespace App\Form\Stock;

use App\Form\Type\TagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ObjetTagForm.
 *
 * @author kevin
 */
class ObjetTagForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tags', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez les tags appliqués à cet objet',
            'required' => false,
            'multiple' => true,
            'choice_label' => 'nom',
            'expanded' => true,
            'class' => \App\Entity\Tag::class,
        ])
            ->add('news', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'label' => 'Ou créez-en de nouveaux',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => TagType::class,
                'mapped' => false,
            ])
            ->add('valider', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'valider']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objetTag';
    }
}

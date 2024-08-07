<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\IntrigueHasDocumentType.
 *
 * @author Kevin F.
 */
class IntrigueHasDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('document', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Ajouter un document concernant cette intrigue',
            'required' => true,
            'class' => \App\Entity\Document::class,
            'choice_label' => 'titre',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\IntrigueHasDocument::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasDocument';
    }
}

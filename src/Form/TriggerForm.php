<?php

namespace App\Form;

use App\Entity\PersonnageTrigger;
use App\Enum\TriggerType;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TriggerForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tag', EnumType::class, [
                'class' => TriggerType::class,
                'label' => 'Tag',
            ]);
        /*->add('tag', TextType::class, [
        'required' => true,
        'label' => 'Tag',
    ]);*/
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonnageTrigger::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'trigger';
    }
}

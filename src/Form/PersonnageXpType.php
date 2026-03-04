<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class PersonnageXpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('xp', IntegerType::class, [
            'label' => 'Expérience à ajouter',
            'required' => true,
            'constraints' => [
                new Type(['integer']),
                new NotBlank(),
            ],
        ])->add('explanation', TextType::class, [
            'label' => 'Explication',
            'required' => true,
            'constraints' => [
                new Length(['max' => 100]),
                new NotBlank(),
            ],
        ]);
    }

}

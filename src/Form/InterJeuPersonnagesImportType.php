<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/** @extends AbstractType<mixed> */
class InterJeuPersonnagesImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ids', TextareaType::class, [
                'label' => 'Liste d\'identifiants (séparés par des virgules)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => '1, 42, 107, …',
                ],
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Ou importer un fichier CSV (une colonne d\'identifiants)',
                'required' => false,
                'constraints' => [
                    new File(mimeTypes: ['text/csv', 'text/plain', 'application/csv']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

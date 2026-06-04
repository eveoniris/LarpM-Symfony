<?php

declare(strict_types=1);

namespace App\Form\FicheRetourGroupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/** @extends AbstractType<mixed> */
class FicheRetourGroupeImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fichier', FileType::class, [
            'label' => 'Fichier CSV ou Excel (.csv, .xlsx, .xls)',
            'required' => true,
            'constraints' => [
                new File(
                    maxSize: '10M',
                    mimeTypes: [
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ],
                    mimeTypesMessage: 'Veuillez fournir un fichier CSV ou Excel valide.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

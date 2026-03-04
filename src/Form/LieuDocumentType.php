<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Lieu;
use App\Repository\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\LieuDocumentForm.
 *
 * @author kevin
 */
class LieuDocumentType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('documents', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez les documents entreposé dans ce lieu en début de jeu',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'class' => \App\Entity\Document::class,
            'choice_label' => 'identity',
            'query_builder' => static fn (DocumentRepository $er) => $er->createQueryBuilder('d')->orderBy('d.code', 'ASC'),
        ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}

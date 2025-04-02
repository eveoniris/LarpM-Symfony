<?php


namespace App\Form\Groupe;

use App\Entity\Document;
use App\Entity\Groupe;
use App\Repository\DocumentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeDocumentForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('documents', EntityType::class, [
            'label' => 'Choisissez les documents possédé par le groupe en début de jeu',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'class' => Document::class,
            'choice_label' => 'identity',
            'query_builder' => static fn (DocumentRepository $er) => $er->createQueryBuilder('d')->orderBy('d.code', 'ASC'),
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeDocument';
    }
}

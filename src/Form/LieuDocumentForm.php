<?php


namespace App\Form;

use App\Repository\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\LieuDocumentForm.
 *
 * @author kevin
 */
class LieuDocumentForm extends AbstractType
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
            'query_builder' => static function (DocumentRepository $er) {
                return $er->createQueryBuilder('d')->orderBy('d.code', 'ASC');
            },
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Lieu::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'lieuDocument';
    }
}

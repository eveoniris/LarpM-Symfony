<?php

namespace App\Form\Espece;

use App\Entity\Espece;
use App\Enum\EspeceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EspeceForm extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
    }

    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ])
            ->add('secret', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'visible' => false,
                    'secrète' => true,
                ],
                'label' => 'Secret',
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => EspeceType::toArray(),
                'choice_value' => static function (string|EspeceType|null $type) {
                    if (null === $type) {
                        return 'Aucune';
                    }

                    if ($type instanceof EspeceType) {
                        return $type->value;
                    }

                    return $type;
                },
                'choice_translation_domain' => 'enum',
                // 'choice_label' => fn (EspeceType $type) => $type->trans($this->translator),
                'label' => 'Type',
            ])
            /* TODO ?
            ->add('bonus', EntityType::class, [
                'required' => false,
                'label' => 'Bonus',
                'class' => Bonus::class,
                'autocomplete' => true,
                'label_html' => true,
                'choice_label' => static fn (Bonus $bonus, $currentKey) => $bonus->getTitre().' - '.$bonus->getDescription(),
            ]) */
        ;
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Espece::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'escepe';
    }
}

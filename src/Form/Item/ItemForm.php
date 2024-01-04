<?php


namespace App\Form\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Monnaie\ItemForm.
 *
 * @author kevin
 */
class ItemForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'required' => true,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'Quelques mots pour décrire votre objet, c\'est le texte décrivant ce que la personne voit, situé au centre de l\'étiquette',
                ],
            ])
            ->add('numero', 'integer', [
                'required' => false,
                'label' => 'Numéro',
                'attr' => [
                    'help' => 'Situé en haut à gauche, il permet de retrouver rapidement l\'objet. NE REMPLISSEZ CETTE CASE QUE SI VOTRE OBJET DE JEU DISPOSE DEJA D\'UN NUMERO (il a été créé pendant le LH2 ou le LH1). Si vous laissez vide, un numero lui sera automatiquement attribué.',
                ],
            ])
            ->add('quality', 'entity', [
                'required' => true,
                'label' => 'Qualité',
                'class' => \App\Entity\Quality::class,
                'property' => 'label',
                'attr' => [
                    'help' => 'Qualité de l\'objet',
                ],
            ])
            ->add('identification', 'choice', [
                'required' => true,
                'label' => 'Identification',
                'choices' => [
                    '81' => 'Rien de spécial',
                    '01' => 'Objet spécial mais non magique',
                    '11' => 'Objet enchanté par magie',
                    '21' => 'Objet enchanté par prêtrise Adonis',
                    '22' => 'Objet enchanté par prêtrise Anu',
                    '23' => 'Objet enchanté par prêtrise Asura',
                    '24' => 'Objet enchanté par prêtrise Bel',
                    '25' => 'Objet enchanté par prêtrise Bori',
                    '26' => 'Objet enchanté par prêtrise Crom',
                    '27' => 'Objet enchanté par prêtrise Derketo',
                    '28' => 'Objet enchanté par prêtrise Erlik',
                    '29' => 'Objet enchanté par prêtrise Ishtar',
                    '30' => 'Objet enchanté par prêtrise Ibis',
                    '31' => 'Objet enchanté par prêtrise Jhebbal Shag',
                    '32' => 'Objet enchanté par prêtrise Jhil',
                    '33' => 'Objet enchanté par prêtrise Mitra',
                    '34' => 'Objet enchanté par prêtrise Pteor',
                    '35' => 'Objet enchanté par prêtrise Set',
                    '36' => 'Objet enchanté par prêtrise Wiccana',
                    '37' => 'Objet enchanté par prêtrise Ymir',
                    '38' => 'Objet enchanté par prêtrise Yun',
                    '39' => 'Objet enchanté par prêtrise Zath',
                    '40' => 'Objet enchanté par prêtrise Ancêtre',
                    '41' => 'Objet enchanté par prêtrise Ashtur',
                    '42' => 'Objet enchanté par prêtrise Tolometh',
                    '43' => 'Objet enchanté par prêtrise Nature',
                    '44' => 'Objet enchanté par prêtrise Jullah-Hanuman',
                    '45' => 'Objet enchanté par prêtrise Dagoth-Dagon',
                    '46' => 'Objet enchanté par prêtrise Louhi',
                    '47' => 'Objet enchanté par prêtrise Shub-niggurath',
                    '48' => 'Objet enchanté par prêtrise Yajur',
                    '49' => 'Objet enchanté par prêtrise Ereshkigal',
                ],
                'attr' => [
                    'help' => "Information sur l'objet",
                ],
            ])
            ->add('quantite', 'integer', [
                'required' => false,
                'label' => 'Quantite',
                'attr' => [
                    'help' => "Nombre d'exemplaire",
                ],
            ])
            ->add('special', 'textarea', [
                'required' => true,
                'label' => 'Description spéciale',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'Quelques mots pour un effet spécial. Ce texte est révélé au joueur si celui-ci réussi à identifier l\'objet',
                ],
            ])
            ->add('couleur', 'choice', [
                'required' => true,
                'label' => 'Couleur de l\'étiquette',
                'choices' => [
                    'orange' => "Orange : Ne prendre que l'etiquette",
                    'bleu' => 'Bleu: Cet objet, indissociable de son etiquette, peut être physiquement volé.'],
                'attr' => [
                    'help' => 'La couleur de l\'étiquette indique si l\'on peux prendre l\'objet en lui-même ou seulement l\'étiquette',
                ],
            ])
            ->add('submit', 'submit', [
                'label' => 'Valider',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Item::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'item';
    }
}

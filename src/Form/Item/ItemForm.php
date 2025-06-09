<?php

namespace App\Form\Item;

use App\Entity\Item;
use App\Entity\Quality;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', TextareaType::class, [
                // TinyMce may bug here if let 'required' => true,
                'label' => 'Description',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'Quelques mots pour décrire votre objet, c\'est le texte décrivant ce que la personne voit, situé au centre de l\'étiquette',
                ],
            ])
            ->add('description_secrete', TextareaType::class, [
                // TinyMce may bug here if let 'required' => true,
                'label' => 'Description secrete',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => "Texte réservé aux initiés ou détenteur de l'objet",
                ],
            ])
            ->add('description_scenariste', TextareaType::class, [
                // TinyMce may bug here if let 'required' => true,
                'label' => 'Description scénaristes',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'Texte réservé aux scénaristes',
                ],
            ])
            ->add('numero', IntegerType::class, [
                'required' => false,
                'label' => 'Numéro',
                'attr' => [
                    'help' => 'Situé en haut à gauche, il permet de retrouver rapidement l\'objet. NE REMPLISSEZ CETTE CASE QUE SI VOTRE OBJET DE JEU DISPOSE DEJA D\'UN NUMERO (il a été créé pendant le LH2 ou le LH1). Si vous laissez vide, un numero lui sera automatiquement attribué.',
                ],
            ])
            ->add('quality', EntityType::class, [
                'required' => true,
                'label' => 'Qualité',
                'class' => Quality::class,
                'choice_label' => 'label',
                'attr' => [
                    'help' => 'Qualité de l\'objet',
                ],
            ])
            ->add('identification', ChoiceType::class, [
                'required' => true,
                'label' => 'Identification',
                'choices' => [
                    '81',
                    '01',
                    '11',
                    '21',
                    '22',
                    '23',
                    '24',
                    '25',
                    '26',
                    '27',
                    '28',
                    '29',
                    '30',
                    '31',
                    '32',
                    '33',
                    '34',
                    '35',
                    '36',
                    '37',
                    '38',
                    '39',
                    '40',
                    '41',
                    '42',
                    '43',
                    '44',
                    '45',
                    '46',
                    '47',
                    '48',
                    '49',
                    '50',
                    '51',
                    '52',
                    '53',
                    '54',
                    '55',
                    '56',
                    '57',
                ],
                'choice_label' => static fn($value) => match ($value) {
                    '81' => 'Rien de spécial',
                    '01' => 'Objet spécial mais non enchanté',
                    '11' => 'Objet enchanté par la compétence Magie',
                    '21' => 'Objet sanctifié par la compétence Prêtrise - Adonis',
                    '22' => 'Objet sanctifié par la compétence Prêtrise - Anu',
                    '23' => 'Objet sanctifié par la compétence Prêtrise - Asura',
                    '24' => 'Objet sanctifié par la compétence Prêtrise - Bel',
                    '25' => 'Objet sanctifié par la compétence Prêtrise - Bori',
                    '26' => 'Objet sanctifié par la compétence Prêtrise - Crom/L\'Éveillé',
                    '27' => 'Objet sanctifié par la compétence Prêtrise - Derketo/Menaka',
                    '28' => 'Objet sanctifié par la compétence Prêtrise - Erlik',
                    '29' => 'Objet sanctifié par la compétence Prêtrise - Ishtar',
                    '30' => 'Objet sanctifié par la compétence Prêtrise - Ibis',
                    '31' => 'Objet sanctifié par la compétence Prêtrise - Jhebbal Shag',
                    '32' => 'Objet sanctifié par la compétence Prêtrise - Jhil',
                    '33' => 'Objet sanctifié par la compétence Prêtrise - Mitra',
                    '34' => 'Objet sanctifié par la compétence Prêtrise - Pteor/Yog',
                    '35' => 'Objet sanctifié par la compétence Prêtrise - Set/Damballah/Akivasha',
                    '36' => 'Objet sanctifié par la compétence Prêtrise - Wiccana',
                    '37' => 'Objet sanctifié par la compétence Prêtrise - Ymir',
                    '38' => 'Objet sanctifié par la compétence Prêtrise - Yun',
                    '39' => 'Objet sanctifié par la compétence Prêtrise - Zath',
                    '40' => 'Objet sanctifié par la compétence Prêtrise - Ancêtre',
                    '41' => 'Objet sanctifié par la compétence Prêtrise - Hastur/Morrigan',
                    '42' => 'Objet sanctifié par la compétence Prêtrise - Tolometh',
                    '43' => 'Objet sanctifié par la compétence Prêtrise - Nature',
                    '44' => 'Objet sanctifié par la compétence Prêtrise - Jullah/Hanuman',
                    '45' => 'Objet sanctifié par la compétence Prêtrise - Dagoth/Dagon',
                    '46' => 'Objet sanctifié par la compétence Prêtrise - Louhi',
                    '47' => 'Objet sanctifié par la compétence Prêtrise - Shub-Niggurath/Sombre Mère',
                    '48' => 'Objet sanctifié par la compétence Prêtrise - Yajur',
                    '49' => 'Objet sanctifié par la compétence Prêtrise - Ereshkigal',
                    '50' => 'Objet sanctifié par la compétence Prêtrise - Yama',
                    '51' => 'Objet sanctifié par la compétence Prêtrise - Ganesh',
                    '52' => 'Objet sanctifié par la compétence Prêtrise - Nebethet',
                    '53' => 'Objet sanctifié par la compétence Prêtrise - Oghm',
                    '54' => 'Objet sanctifié par la compétence Prêtrise - Dagda',
                    '55' => 'Objet sanctifié par la compétence Prêtrise - Xotli/Ong',
                    '56' => 'Objet sanctifié par la compétence Prêtrise - Affras',
                    '57' => 'Lié aux chaudrons (mais aucun en particulier)',
                },
                'attr' => [
                    'help' => "Information sur l'objet",
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'required' => false,
                'label' => 'Quantite',
                'attr' => [
                    'help' => "Nombre d'exemplaire",
                ],
            ])
            ->add('special', TextareaType::class, [
                // TinyMce may bug here if let 'required' => true,
                'label' => 'Description spéciale',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                    'help' => 'Quelques mots pour un effet spécial. Ce texte est révélé au joueur si celui-ci réussi à identifier l\'objet',
                ],
            ])
            ->add('couleur', ChoiceType::class, [
                'required' => true,
                'label' => 'Couleur de l\'étiquette',
                'choices' => [
                    "Orange : Ne prendre que l'etiquette" => 'orange',
                    'Bleu: Cet objet, indissociable de son etiquette, peut être physiquement volé.' => 'bleu',
                ],
                'attr' => [
                    'help' => 'La couleur de l\'étiquette indique si l\'on peux prendre l\'objet en lui-même ou seulement l\'étiquette',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Item::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
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
        return 'item';
    }
}

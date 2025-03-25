<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('carte_bancaire', TextType::class, [
                'label' => 'Numéro de carte bancaire',
                'required' => true,
            ])
            ->add('expiration', TextType::class, [
                'label' => 'Date d\'expiration (MM/AA)',
                'required' => true,
            ])
            ->add('cvv', TextType::class, [
                'label' => 'Code CVV',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}

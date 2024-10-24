<?php

namespace App\Form;

use App\Entity\Trick;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('content')    
            ->add('files', FileType::class, [
                'label' => 'Saisir une ou plusieurs image',
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'data_class' => null,
                // 'constraints' => [
                //     new File([
                //         'mimeTypes' => [
                //             'image/jpeg',
                //             'image/jpg',
                //             'image/png',
                //         ],
                //         'mimeTypesMessage' => 'Veuillez saisir un format .jpeg , .jpg ou .png',
                //     ])
                // ],
                'label_attr' => [
                    'class' => 'btn btn-light',
                ],
            ])
            ->add('primary_image', HiddenType::class, [
                'mapped' => false, 
                'required' => false,
               
            ])
            ->add('links', CollectionType::class, [
                'entry_type' => UrlType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'required' => false,

            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
            'allow_extra_fields' => true,
        ]);
    }
}

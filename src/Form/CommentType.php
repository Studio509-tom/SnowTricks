<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content' ,TextareaType::class , [
                "label" => "Commentaire :"
            ])
            ->add('trick' ,HiddenType::class, [
                'data' => $options['trick'] ? $options['trick']->getId() : null, // Si Trick existe, utiliser son ID
                'mapped' => false, // Ne pas mapper directement à l'entité Comment
            ]);

            
            // ->add('user', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('trick', EntityType::class, [
            //     'class' => Trick::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'trick' => null ,
        ]);
    }
}

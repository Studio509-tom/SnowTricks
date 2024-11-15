<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Trick;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private $tricksDirectory, $imageLocation;
    private $defaultDirectory, $defaultsDirectory;

    public function __construct(UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository ,string $tricksDirectory ,string  $defaultsDirectory)
    {
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->imageLocation = $tricksDirectory ;
        $this->defaultDirectory = $defaultsDirectory;
    }

    public function load(ObjectManager $manager): void
    {
        // Administrateur
        $user = new User();
        $password = $this->passwordHasher->hashPassword(
            $user,
            'adminpassword'
        );
        $user->setEmail("admin@demo.fr");
        $user->setFirstname("admin");
        $user->setLastname("admin");
        $user->setVerified(true);
        $user->setPassword($password);
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();
        // Figure 1
        $content = "Un ollie est une manière spécifique de « sauter », de décoller du sol verticalement en cours de ride. En général, nous recommandons d’apprendre le ollie en premier, car il s’agit d’une étape cruciale pour l’apprentissage d’autres figures de snowboard. Une fois que vous maîtrisez le ollie, vous pouvez l’utiliser pour les figures sur plat, sur rail et les sauts.";
        $today = new \DateTime();
        $trick = $this->setTrick('Le ollie', $content, $user, $today);
        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['ollie.jpeg']);
        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'ollie.jpeg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'ollie.jpeg');


        // Figure 2
        $content = "Avant de faire une explication détaillée sur le Nose Press et le Tail Press, il faut d’abord connaitre les termes anglais du ‘’Nose’’ et du ‘’Tail’’, qui se traduisent par ‘’le nez’’ et ‘’la queue’’ en français. Sur un snowboard, on appelle le devant de la planche le Nose/nez et l’arrière de la planche le Tail/queue. Que tu sois en Regular, en Goofy ou en Switch, le nez sera toujours pointé dans la direction de la piste.
        Revenons maintenant à notre explication. Un Tail Press consiste à simplement transférer ton poids sur ta jambe arrière tout en soulevant légèrement ta jambe avant, ce qui soulèvera l'avant de la planche. Le Nose Press est l'inverse. Le poids est transféré sur la jambe avant et l'arrière de la planche se plie vers le haut. Il s'agit d'une astuce classique, mais basique, qui donne à n'importe quel planchiste un look 'steezy'. À noter que, si tu as une planche flexible, tu devrais avoir plus d’effets et de hauteurs au moment de soulever ta planche !
        Le conseil des pros : Commence par le faire sur un espace plat sans mouvement pour trouver le bon équilibre, et dès que tu arrives à le faire sur le plat sans bouger, tu peux ensuite le faire en mouvement. Si tu maitrises les deux figures en mouvement, tu peux essayer de le faire sur un box au snowpark.";
        $trick = $this->setTrick('Le Nose Press/Tail Press', $content, $user, $today);

        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['Nose.jpg']);

        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'Nose.jpg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'Nose.jpg');

        // Figure 3
        $content = "Le Jib est l'une des figures de base à apprendre quand tu te lances dans le park, car il est utilisé sur la plupart des figures freestyle. Le jibbing consiste à chevaucher, sauter ou glisser sur tout ce qui n'est pas une surface piquée, comme les rails, les bancs ou une bûche. Comme pour le Butter, il faut éviter d’utiliser les lames/bords sur les rails et les boxs, car elles ne t’aideront pas à t’arrêter si jamais tu perds l’équilibre";
        $trick = $this->setTrick('Le Jib', $content, $user, $today);

        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['Jib.jpg']);

        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'Jib.jpg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'Jib.jpg');

        // Figure 4
        $content = "Le Indy Grab est la figure de base pour saisir sa planche dans les airs, mais il y en a beaucoup d'autres pour diversifier tes sauts. Pour ceux qui cherchent à impressionner, essayez le Tail Grab comme prochaine étape. Comme pour le Indy Grab, commence par faire un Ollie pour prendre de la hauteur depuis le saut, et une fois en l’air, attrapes la queue de la planche avec ta main arrière. C'est aussi simple que cela (ou pas si simple, mais personne ne se doutera de rien).";
        $trick = $this->setTrick('Le Tail Grab', $content, $user, $today);

        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['Trail-grab.jpg']);

        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'Trail-grab.jpg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'Trail-grab.jpg');
        // Figure 5
        $content = "Le Backflip est l'une des figures les plus emblématiques du snowboard, et on ne pouvait pas l’exclure de notre liste ! Une fois que tu as quitté le kicker et que tu as assez de hauteur et d'élan, il faut jeter ton poids en arrière pour faire une rotation verticale - ou un Flip - pour avoir la tête en bas et les jambes en haut, tout en relâcher tes jambes au bon moment pour atterrir. Tu as probablement eu quelques accidents désagréables en le perfectionnant, mais cela en vaut la peine lorsque tu vois les visages et les acclamations de tes spectateurs. ";
        $trick = $this->setTrick('Le Backflip', $content, $user, $today);

        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['snowboarder-backflip.jpg']);

        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'snowboarder-backflip.jpg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'snowboarder-backflip.jpg');
        // Figure 6
        $content = "Le Cork est une variation avancée d'une rotation, où tu ne te contentes pas de tourner à la verticale, mais où ton corps tourne en fait hors de l'axe. Au milieu du saut, tu peux faire un 360 en Frontside ou en Backside, pendant que tes jambes et ta planche tournent vers le haut, de sorte que le haut de ton corps se trouve sous ta planche. Pour les experts, l'exécution du Cork te fera tourner à l'envers, ce qui est assez fou et difficile à comprendre.";
        $trick = $this->setTrick('Une rotation Cork', $content, $user, $today);

        $title_trick = $trick->getTitle();
        $slug = $this->slugify($title_trick);
        $trick->setSlug($slug);
        $trick->setFirstFile(['Corck.jpeg']);

        $manager->persist($trick);
        $manager->flush();
        $sourceFile = $this->defaultDirectory .'\\'. 'Corck.jpeg';
        $path = $this->imageLocation . $trick->getId();
        mkdir($path);
        copy($sourceFile, $path . '/' .'Corck.jpeg');

    }

    public function setTrick(string $title, string $content, User $user, \DateTime $date): Trick
    {
        $trick = new Trick();
        $trick->setContent($content);
        $trick->setTitle($title);
        $trick->setFiles([]);
        $trick->setLinks([]);
        $trick->setUser($user);
        $trick->setDateCreate($date);
        $trick->setFirstFile([]);

        return $trick;
    }

    function slugify(string $text): string
    {
        // Remplacer les accents par leur équivalent ASCII
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        // Remplacer les espaces par des tirets
        $text = str_replace(' ', '-', $text);
        // Supprimer les caractères non-alphabétiques et non-numériques sauf les tirets
        $text = preg_replace('/[^a-zA-Z0-9\-]/', '', $text);
        // Remplacer plusieurs tirets consécutifs par un seul
        $text = preg_replace('/-+/', '-', $text);
        // Supprimer les tirets en début et fin de chaîne
        $text = trim($text, '-');
        // Passer en minuscules
        $text = strtolower($text);
        return $text;
    }
}

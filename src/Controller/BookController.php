<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    /**
     * @Route("/", name="book")
     */
    public function index(Request $request)
    {
        $book = new Book();

        $bookForm = $this->createForm(BookType::class, $book);
        $bookForm->handleRequest($request);

        if ($bookForm->isSubmitted() && $bookForm->isValid()){

            //pompé de https://symfony.com/doc/4.4/controller/upload_file.html

            //récupère l'objet UploadedFile représentant le fichier uploadé
            $coverFile = $book->getCoverFile();
            if ($coverFile) {
                //génère un nom unique, safe pour les urls
                $safeFilename = uniqid();
                //ajoute l'extension au nom du fichier
                $newFilename = $safeFilename.".".$coverFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    //déplace le fichier depuis son emplacement temporaire sur le serveur
                    //vers notre répertoire à nous
                    $coverFile->move(
                        //ce paramètre est défini dans config/services.yaml
                        $this->getParameter('upload_dir'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                //on stocke le nom du fichier dans notre entité pour le sauvegarder en bdd
                //le coverFile lui ne sera pas sauvegardé en bdd !
                $book->setCover($newFilename);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();

            $image = new \claviska\SimpleImage();
            $image
                ->fromFile($this->getParameter('upload_dir') . "/" . $newFilename)                     // load image.jpg
                ->thumbnail(320, 320)                          // resize to 320x200 pixels
                ->colorize('DarkBlue')                      // tint dark blue
                ->toFile($this->getParameter('upload_dir') . "/thumbnails/" . $newFilename)      // convert to PNG and save a copy to new-image.png
                ;
        }

        return $this->render('book/index.html.twig', [
            'bookForm' => $bookForm->createView(),
        ]);
    }
}

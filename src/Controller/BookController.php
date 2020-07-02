<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();
        }

        return $this->render('book/index.html.twig', [
            'bookForm' => $bookForm->createView(),
        ]);
    }
}

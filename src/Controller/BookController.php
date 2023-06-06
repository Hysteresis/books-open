<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    public function getAllBooks(
        BookRepository $bookRepository, 
        SerializerInterface $serializer,
        ): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);

        return new JsonResponse(
            $jsonBookList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{id}', name: 'app_book_id', methods: ['GET'])]
    public function getOneBook(
        Book $book,
        SerializerInterface $serializer, 
        ): JsonResponse
    {
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

        
        return new JsonResponse(
            $jsonBook,
            Response::HTTP_OK,
            ['accept' => 'json'],
            true
        );
    }

    #[Route('/api/books/{id}', name: 'app_book_delete', methods: ['DELETE'])]
    public function deleteBook(
        Book $book, 
        EntityManagerInterface $em
        ): JsonResponse
    {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
        );
    }

    #[Route('/api/books', name: 'app_book_create', methods: ['POST'])]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
    ): JsonResponse
    {
        // tranforme json en objet $book
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json' );
        
        // recuperer les donnes de la requete et les stocker dans un tableau
        $content = $request->toArray();
        // recuperer l'id author et s'il nest pas défini on met -1 par défaut
        $idAuthor = $content['idAuthor'] ?? -1;

        $book->setAuthor($authorRepository->find($idAuthor));
        
        $em->persist($book);
        $em->flush();
        //on renvoie le $book en json dans la response car c'est une pratique
        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

        //on creer l'url sur laquelle le livre peut etre trouvé
        $location = $urlGenerator->generate(
            'app_book_id',
            ['id' => $book->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $jsonBook,
            Response::HTTP_CREATED,
            ['location' => $location],
            true
        );
    }

    #[Route('/api/books/{id}', name:'app_book_update', methods: ['PUT'])]
    public function updateBook(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        Book $currentBook,
        AuthorRepository $authorRepository,
        Request $request,

    ): JsonResponse 
    {
        $updatedBook = $serializer->deserialize(
            $request->getContent(), 
            Book::class, 
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
        );
        $content = $request->toArray();

        $idAuthor = $content['idAuthor'] ?? -1;

        $updatedBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($updatedBook);
        $em->flush();


        return new JsonResponse(
            $updatedBook,
            JsonResponse::HTTP_NO_CONTENT,
        );
    }
}

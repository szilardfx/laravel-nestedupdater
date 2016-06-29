<?php
namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\ModelUpdater;
use Czim\NestedModelUpdater\Test\Helpers\Models\Author;
use Czim\NestedModelUpdater\Test\Helpers\Models\Comment;
use Czim\NestedModelUpdater\Test\Helpers\Models\Genre;
use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class ElaborateModelUpdaterTest extends TestCase
{

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_hasmany_relation()
    {
        $post    = $this->createPost();
        $comment = $this->createComment($post);
        
        $data = [
            'comments' => [
                [
                    'id'    => $comment->id,
                    'title' => 'updated title',
                ],
                [
                    'title' => 'new title',
                    'body'  => 'new body',
                ]
            ]
        ];
        
        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
        ]);

        $this->seeInDatabase('comments', [
            'title' => 'new title',
            'body'  => 'new body',
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_hasone_relation()
    {
        $post = $this->createPost();

        $data = [
            'comment_has_one' => [
                'title' => 'created title',
                'body'  => 'created body',
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('comments', [
            'title' => 'created title',
            'body'  => 'created body',
        ]);

        $comment = $post->commentHasOne()->first();
        $this->assertInstanceOf(Comment::class, $comment);


        // and update it

        $data = [
            'comment_has_one' => [
                'id'    => $comment->id,
                'title' => 'updated title',
            ],
        ];

        $updater->update($data, $post);

        $this->seeInDatabase('comments', [
            'id'    => $comment->id,
            'title' => 'updated title',
            'body'  => 'created body',
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_belongstomany_relation()
    {
        $post   = $this->createPost();
        $author = $this->createAuthor();

        $data = [
            'posts' => [
                [
                    'id'     => $post->id,
                    'title'  => 'updated title',
                    'body'   => 'updated body',
                ],
                [
                    'title'  => 'very new title',
                    'body'   => 'very new body',
                ]
            ]
        ];

        $updater = new ModelUpdater(Author::class);
        $updater->update($data, $author);

        $this->seeInDatabase('posts', [
            'id'    => $post->id,
            'title' => 'updated title',
            'body'  => 'updated body',
        ]);

        $this->seeInDatabase('posts', [
            'title' => 'very new title',
            'body'  => 'very new body',
        ]);

        $this->seeInDatabase('author_post', [
            'post_id'   => $post->id,
            'author_id' => $author->id,
        ]);
    }

    /**
     * @test
     */
    function it_creates_and_updates_a_nested_morphmany_relation()
    {
        $post = $this->createPost();
        $tag  = $this->createTag();

        $data = [
            'tags' => [
                [
                    'id'   => $tag->id,
                    'name' => 'updated tag',
                ],
                [
                    'name' => 'new tag',
                ]
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('tags', [
            'id'            => $tag->id,
            'taggable_id'   => $post->id,
            'taggable_type' => get_class($post),
            'name'          => 'updated tag',
        ]);

        $this->seeInDatabase('tags', [
            'taggable_id'   => $post->id,
            'taggable_type' => get_class($post),
            'name'          => 'new tag',
        ]);
    }

    /**
     * @test
     */
    function it_updates_a_nested_related_record_with_nonstandard_primary_key()
    {
        $post    = $this->createPost();
        $special = $this->createSpecial('special-1');

        $data = [
            'specials' => [
                [
                    'special' => $special->getKey(),
                    'name'    => 'updated special',
                ],
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('specials', [
            'special' => $special->getKey(),
            'name'    => 'updated special',
        ]);
    }

    /**
     * @test
     */
    function it_creates_a_nested_related_record_with_nonincrementing_primary_key_if_the_key_does_not_exist()
    {
        $post = $this->createPost();

        $data = [
            'specials' => [
                [
                    'special' => 'special-new',
                    'name'    => 'new special',
                ],
            ]
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('specials', [
            'special' => 'special-new',
            'name'    => 'new special',
        ]);
    }

    // ------------------------------------------------------------------------------
    //      Detaching and Deleting Omitted
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_deletes_omitted_nested_belongsto_relations_on_replacement_and_dissociation_if_configured_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.genre.delete-detached', true);

        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $oldGenreId = $genre->id;

        $this->seeInDatabase('posts', [ 'id' => $post->id, 'genre_id' => $oldGenreId ]);

        // check if it is deleted after dissociation

        $data = [
            'genre' => null,
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('posts', [ 'id' => $post->id, 'genre_id' => null ]);
        $this->notSeeInDatabase('genres', [ 'id' => $oldGenreId ]);

        // reset

        $post->delete();
        $post  = $this->createPost();
        $genre = $this->createGenre();
        $post->genre()->associate($genre);
        $post->save();

        $oldGenreId = $genre->id;

        $this->seeInDatabase('posts', [ 'id' => $post->id, 'genre_id' => $oldGenreId ]);

        // check if it is deleted after replacement

        $data = [
            'genre' => [
                'name' => 'replacement genre'
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $genre = Genre::where('name', 'replacement genre')->first();
        $this->assertInstanceOf(Genre::class, $genre);

        $this->seeInDatabase('posts', [ 'id' => $post->id, 'genre_id' => $genre->id ]);
        $this->notSeeInDatabase('genres', [ 'id' => $oldGenreId ]);
    }

    /**
     * @test
     */
    function it_detaches_omitted_nested_belongstomany_relations()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.link-only', false);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
                [
                    'name'   => 'New Author',
                    'gender' => 'f',
                ]
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $authorC = Author::latest()->first();
        $this->assertInstanceOf(Author::class, $authorC);

        $this->seeInDatabase('authors', [ 'id' => $authorB->id ]);
        $this->seeInDatabase('author_post',    [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->seeInDatabase('author_post',    [ 'post_id' => $post->id, 'author_id' => $authorC->id ]);
        $this->notSeeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
    }

    /**
     * @test
     */
    function it_does_not_detach_omitted_nested_belongstomany_relations_if_configured_not_to()
    {
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors.detach', true);

        // setup

        $post    = $this->createPost();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();
        $post->authors()->sync([ $authorA->id, $authorB->id ]);

        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);

        $data = [
            'authors' => [
                $authorA->id,
            ],
        ];

        // test

        $updater = new ModelUpdater(Post::class);
        $updater->update($data, $post);

        $this->seeInDatabase('authors', [ 'id' => $authorB->id ]);
        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorA->id ]);
        $this->seeInDatabase('author_post', [ 'post_id' => $post->id, 'author_id' => $authorB->id ]);
    }



    // ------------------------------------------------------------------------------
    //      Full Stack
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_creates_deeply_nested_structure_of_all_new_models()
    {
        // allow creating authors through posts
        $this->app['config']->set('nestedmodelupdater.relations.' . Post::class . '.authors', [
            'link-only' => false,
        ]);
        
        $data = [
            'title' => 'new title',
            'body' => 'new body',
            'genre' => [
                'name' => 'new genre',
            ],
            'comments' => [
                [
                    'title'  => 'title 1',
                    'body'   => 'body 1',
                    'author' => [
                        'name' => 'Author B',
                    ],
                ],
                [
                    'title'  => 'title 2',
                    'body'   => 'body 2',
                    'author' => [
                        'name' => 'Author C',
                    ],
                ]
            ],
            'authors' => [
                [
                    'name'   => 'Author A',
                    'gender' => 'f',
                ],
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);

        // check the whole structure

        $post = Post::latest()->first();
        $this->assertInstanceOf(Post::class, $post);

        $genre = Genre::latest()->first();
        $this->assertInstanceOf(Genre::class, $genre);

        $commentAuthorB = Author::where('name', 'Author B')->first();
        $this->assertInstanceOf(Author::class, $commentAuthorB);
        $commentAuthorC = Author::where('name', 'Author C')->first();
        $this->assertInstanceOf(Author::class, $commentAuthorC);

        $this->seeInDatabase('posts', [
            'id'       => $post->id,
            'title'    => 'new title',
            'body'     => 'new body',
            'genre_id' => $genre->id,
        ]);

        $this->seeInDatabase('genres', [
            'id'   => $genre->id,
            'name' => 'new genre',
        ]);

        $this->seeInDatabase('comments', [
            'title'     => 'title 1',
            'body'      => 'body 1',
            'author_id' => $commentAuthorB->id,
        ]);

        $this->seeInDatabase('comments', [
            'title'     => 'title 2',
            'body'      => 'body 2',
            'author_id' => $commentAuthorC->id,
        ]);

        $this->seeInDatabase('authors', [
            'name' => 'Author A',
        ]);
    }
    
    /**
     * @test
     */
    function it_creates_a_deeply_nested_structure_with_linked_models()
    {
        $genre  = $this->createGenre();
        $authorA = $this->createAuthor();
        $authorB = $this->createAuthor();

        $data = [
            'title' => 'new title',
            'body' => 'new body',
            'genre' => $genre->id,
            'comments' => [
                [
                    'title'  => 'title 1',
                    'body'   => 'body 1',
                    'author' => [
                        'id' => $authorA->id,
                    ],
                ],
                [
                    'title'  => 'title 2',
                    'body'   => 'body 2',
                    'author' => $authorB->id,
                ]
            ],
            'authors' => [
                $authorA->id,
                [
                    'id' => $authorB->id,
                ],
            ],
        ];

        $updater = new ModelUpdater(Post::class);
        $updater->create($data);

        // check the whole structure

        $post = Post::latest()->first();
        $this->assertInstanceOf(Post::class, $post);

        $this->seeInDatabase('posts', [
            'id'       => $post->id,
            'title'    => 'new title',
            'body'     => 'new body',
            'genre_id' => $genre->id,
        ]);

        $this->seeInDatabase('comments', [
            'title'     => 'title 1',
            'body'      => 'body 1',
            'author_id' => $authorA->id,
        ]);

        $this->seeInDatabase('comments', [
            'title'     => 'title 2',
            'body'      => 'body 2',
            'author_id' => $authorB->id,
        ]);

        $this->seeInDatabase('author_post', [
            'post_id'   => $post->id,
            'author_id' => $authorA->id,
        ]);

        $this->seeInDatabase('author_post', [
            'post_id'   => $post->id,
            'author_id' => $authorB->id,
        ]);
    }

}

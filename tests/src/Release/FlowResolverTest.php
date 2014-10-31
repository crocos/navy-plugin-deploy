<?php
namespace Crocos\Plugin\DeployPlugin\Release;

use Navy\GitHub\WebHook\PullRequest;
use Navy\GitHub\WebHook\Comment;
use Navy\GitHub\WebHook\CommentCollection;
use Phake;

class FlowResolverTest extends \PHPUnit_Framework_TestCase
{
    protected $flows = [];
    protected $resolver;

    protected function setUp()
    {
        $this->flows['shipit'] =  new Flow('shipit', [
            'keyword' => 'shipit',
            'process' => [
                'staging',
            ],
        ]);

        $this->flows['+1'] = new Flow('+1', [
            'keyword'            => '+1',
            'permit_self_review' => true,
            'process'            => [
                'staging',
                'check',
            ],
        ]);

        $this->resolver = new FlowResolver($this->flows);
    }

    /**
     * @dataProvider getResolveByPullRequestData
     */
    public function testResolveByPullRequest($expectedFlow, $comments)
    {
        $pr = Phake::mock(PullRequest::class);
        Phake::when($pr)->getUser()->thenReturn('yudoufu');

        foreach ($comments as $i => $comment) {
            $comments[$i] = $this->createComment($comment[0], $comment[1]);
        }

        Phake::when($pr)->getComments()->thenReturn(new CommentCollection($comments));

        $flow = $this->resolver->resolveByPullRequest($pr);

        if ($expectedFlow === null) {
            $this->assertNull($flow);
        } else {
            $this->assertEquals($this->flows[$expectedFlow], $flow);
        }
    }

    public function getResolveByPullRequestData()
    {
        return [
            // expected, comments<user, body>
            [
                '+1', [
                    ['fivestar', '+1'],
                ]
            ],
            [
                'shipit', [
                    ['fivestar', 'ok'],
                    ['fivestar', 'shipit'],
                ]
            ],
            [
                'shipit', [
                    ['fivestar', '+1'],
                    ['fivestar', 'shipit'],
                ]
            ],
            [
                'shipit', [
                    ['fivestar', ':+1: :shipit:'],
                ]
            ],
            [
                '+1', [
                    ['yudoufu', '+1'],
                ]
            ],
            [
                null, [
                    ['yudoufu', 'shipit'],
                ]
            ],
            [
                null, [
                    ['fivestar', 'ng'],
                ]
            ],
            [
                null, [
                ]
            ],
        ];
    }

    protected function createComment($user, $body)
    {
        $comment = Phake::mock(Comment::class);
        Phake::when($comment)->getUser()->thenReturn($user);
        Phake::when($comment)->getBody()->thenReturn($body);

        return $comment;
    }
}

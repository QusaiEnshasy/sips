<?php

namespace App\Http\Controllers\tasks;

use App\Http\Controllers\Controller;
use App\Services\TrelloService;

class TrelloController extends Controller
{
    protected TrelloService $trello;

    public function __construct(TrelloService $trello)
    {
        $this->trello = $trello;
    }

    public function index()
    {
        $boards = $this->trello->getBoards();
        return view('trello.index', compact('boards'));
    }

    public function createCard()
    {
        $card = $this->trello->createCard(
            listId: 'YOUR_LIST_ID',
            name: 'Task جديدة',
            desc: 'وصف المهمة'
        );

        return response()->json($card);
    }
}

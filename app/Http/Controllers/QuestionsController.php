<?php

namespace App\Http\Controllers;

use App\Question;
use App\Tag;
use App\TagsQuestion;
use App\User;
use App\Answer;
use Auth;
use Validator;

use Illuminate\Http\Request;

// 定義
define('MAX','5');

class QuestionsController extends Controller
{
    public function new()
    {
        $tags = Tag::get();
        return view('questions/new',['tags'=>$tags]);
        // テンプレート「listing/new.blade.php」を表示します。
    }
    // ===ここまでカードを新規作成する処理の追加（フォームへの遷移）===


    // ===ここからカードを新規作成する処理の追加（データベースへの保存）===
    public function store(Request $request)
    {
        $messages = [
                'name.required' => 'タイトルを入力してください。',
                'name.max'=>'タイトルは255文字以内で入力してください。',
                'content.required'=>'内容を入力してください。',
                'tags.required'=>'タグを選択してください。'
        ];
        //Validatorを使って入力された値のチェック(バリデーション)処理　（今回は256以上と空欄の場合エラーになります）
        $validator = Validator::make($request->all() , ['name' => 'required|max:256','content'=>['required', 
            function($attribute, $value, $fail){
                if(strlen($value)>65535){
                    $fail('メモは65535バイト以内で入力してください。(現在'.strlen($value).'バイト)');
                }
            }
        ],'tags'=>'required'],$messages);
        
        if ($validator->fails())
        {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }
        
        // 入力に問題がなければCardモデルを介して、タイトルとかをqテーブルに保存
        //eval(\Psy\sh());
        $question = new Question;
        $question->title = $request->name;
        $question->user_id = Auth::id();
        $question->crear_flag = false;
        $question->content = $request->content;
        $question->want_know_count=0;
        $question->save();
        

        foreach($request->tags as $tagid){
            $q = new TagsQuestion;
            $q->tags_id = $tagid;
            $q->questions_id = $question->id;
            $q->save();
        };
        
        // 「/」 ルートにリダイレクト
        return redirect('/');
    }
    
    public function index(){

        // ページの初期値
        $page_id = 1;
        // ページ数を取得
        $questions = Question::get();
        $max_page = ceil(count($questions)/MAX);
        
        // 開始地点と終了地点の質問idを取得
        $end_id = $page_id * MAX;
        $start_id = $end_id - MAX + 1;
        
        // 配列の初期化
        $questions = [];
        
        // そのページの質問を取得
        for($i = $start_id; $i <= $end_id; $i++){
            array_push($questions,Question::find($i));
        }
        
        // トップviewにデータを送る
        return view('questions/index',['questions' => $questions, 'page_id' => $page_id, 'max_page' => $max_page]);
    }
    
    public function paging($page_id){
        
        // ページ数を取得
        $questions = Question::get();
        $max_page = ceil(count($questions)/MAX);
        
        // 開始地点と終了地点の質問idを取得
        $end_id = $page_id * MAX;
        $start_id = $end_id - MAX + 1;
        
        // 配列の初期化
        $questions = [];
        
        // そのページの質問を取得
        for($i = $start_id; $i <= $end_id && Question::find($i) != null; $i++){
            array_push($questions,Question::find($i));
        }
        
        // トップviewにデータを送る
        return view('questions/index',['questions' => $questions, 'page_id' => $page_id, 'max_page' => $max_page]);
    
    }
    
    public function show($question_id)
    {
        // 質問をすべて取得
        $question = Question::find($question_id);
        $show_user= Auth::id();
        
        return view('questions/show',['question' => $question,'show_user'=>$show_user]);
    }
    public function show_userpage(){
        
        // ユーザ番号を取得
        $user_id = Auth::user()->id;
        
        // Questionモデルを介してデータを取得
        $questions = Question::where('user_id',$user_id)->get();
        
        // Answerモデルを介してデータを取得
        $answers = Answer::where('user_id',$user_id)->get();
        
        // Userモデルを介してデータを取得
        $user = User::find($user_id);
        
        // データをユーザ詳細画面に送る
        return view('users/show',['user_id' => $user_id, 'questions' => $questions, 'answers' => $answers, 'user' => $user]);
        
    }
    
    //ログイン画面に遷移
    public function login(){
        return view('login');
    }
    //登録画面に遷移
    public function register(){
        return view('register');
    }
    //質問投稿画面に遷移
    public function question_new(){
        return view('questions/new');
    }
    //ユーザー詳細画面に遷移
    public function user_show(){
        return view('users/show');
    }
}

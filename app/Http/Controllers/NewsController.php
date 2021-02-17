<?php

namespace App\Http\Controllers;

use App\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        if ($request->bulk_action_btn === 'update_status'){
            News::query()->whereIn('id', $request->bulk_ids)->update(['status' => $request->status]);
            return back()->with('success', __a('bulk_action_success'));
        }
        if ($request->bulk_action_btn === 'delete'){
            if(config('app.is_demo')) return back()->with('error', __a('demo_restriction'));

            News::query()->whereIn('id', $request->bulk_ids)->delete();
            return back()->with('success', __a('bulk_action_success'));
        }

        $title = __a('pages');
        $posts = News::whereType('page')->orderBy('id', 'desc')->paginate(20);
        return view('admin.News.pages', compact('title', 'posts'));
    }

    public function posts(Request $request){
        if ($request->bulk_action_btn === 'update_status'){
            News::query()->whereIn('id', $request->bulk_ids)->update(['status' => $request->status]);
            return back()->with('success', __a('bulk_action_success'));
        }
        if ($request->bulk_action_btn === 'delete'){
            if(config('app.is_demo')) return back()->with('error', __a('demo_restriction'));

            News::query()->whereIn('id', $request->bulk_ids)->delete();
            return back()->with('success', __a('bulk_action_success'));
        }

        $title = __a('posts');
        $posts = News::whereType('post')->orderBy('id', 'desc')->paginate(20);

        return view('admin.News.posts', compact('title', 'posts'));
    }

    public function createPost(){
        $title = __a('create_new_post');

        return view('admin.News.post_create', compact('title'));
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storePost(Request $request){
        if(config('app.is_demo')) return back()->with('error', __a('app.feature_disable_demo'));

        $user = Auth::user();
        $rules = [
            'title'     => 'required|max:220',
            'post_content'   => 'required',
        ];
        $this->validate($request, $rules);

        $slug = unique_slug($request->title, 'Post');
        $data = [
            'user_id'               => $user->id,
            'title'                 => clean_html($request->title),
            'slug'                  => $slug,
            'post_content'          => clean_html($request->post_content),
            'type'                  => 'post',
            'status'                => '1',
            'feature_image'         => $request->feature_image,
        ];

        News::create($data);
        return redirect(route('News'))->with('success', __a('post_has_been_created'));
    }


    public function editPost($id){
        $title = __a('edit_post');
        $post = News::find($id);

        return view('admin.News.edit_post', compact('title', 'post'));
    }

    public function updatePost(Request $request, $id){
        if(config('app.is_demo')) return back()->with('error', __a('app.feature_disable_demo'));

        $rules = [
            'title'     => 'required|max:220',
            'post_content'   => 'required',
        ];
        $this->validate($request, $rules);
        $page = News::find($id);

        $data = [
            'title'                 => clean_html($request->title),
            'post_content'          => clean_html($request->post_content),
            'feature_image'         => $request->feature_image,
        ];

        $page->update($data);
        return redirect()->back()->with('success', __a('post_has_been_updated'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(){
        $title = __a('pages');
        return view('admin.News.page_create', compact('title'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request){
        if(config('app.is_demo')) return back()->with('error', __a('app.feature_disable_demo'));

        $user = Auth::user();
        $rules = [
            'title'     => 'required|max:220',
            'post_content'   => 'required',
        ];
        $this->validate($request, $rules);

        $slug = unique_slug($request->title, 'Post');
        $data = [
            'user_id'               => $user->id,
            'title'                 => clean_html($request->title),
            'slug'                  => $slug,
            'post_content'          => clean_html($request->post_content),
            'type'                  => 'page',
            'status'                => 1,
        ];

        News::create($data);
        return redirect(route('pages.News'))->with('success', __a('page_has_been_created'));
    }

    /**
     * @param $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id){
        $title = __a('edit_page');
        $post = News::find($id);
        return view('admin.News.edit_page', compact('title', 'post'));
    }

    public function updatePage(Request $request, $id){
        if(config('app.is_demo')) return back()->with('error', __a('app.feature_disable_demo'));

        $rules = [
            'title'     => 'required|max:220',
            'post_content'   => 'required',
        ];
        $this->validate($request, $rules);
        $page = News::find($id);

        $data = [
            'title'                 => clean_html($request->title),
            'post_content'          => clean_html($request->post_content),
        ];

        $page->update($data);
        return redirect()->back()->with('success', __a('page_has_been_updated'));
    }

    public function showPage($slug){
        $page = News::whereSlug($slug)->first();

        if (! $page){
            return view('theme.error_404');
        }
        $title = $page->title;
        return view('theme.single_page', compact('title', 'page'));
    }

    public function blog(){
        $title ="الأخبار";
        $posts = News::post()->publish()->paginate(20);
        return view(theme('News'), compact('title', 'posts'));
    }

    public function authorPosts($id){
        $posts = News::whereType('post')->whereUserId($id)->paginate(20);
        $user = User::find($id);
        $title = $user->name."'s ".trans('app.blog');
        return view('theme.blog', compact('title', 'posts'));
    }

    public function postSingle($slug){
        $post = News::whereSlug($slug)->first();
        if ( ! $post){
            abort(404);
        }
        $title = $post->title;

        if ($post->type === 'post'){
            return view(theme('single_post'), compact('title', 'post'));
        }
        return view(theme('single_page'), compact('title', 'post'));
    }

    public function postProxy($id){
        $post = News::where('id', $id)->orWhere('slug', $id)->first();
        if ( ! $post){
            abort(404);
        }
        return redirect(route('post', $post->slug));
    }

}

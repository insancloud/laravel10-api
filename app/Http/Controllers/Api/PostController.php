<?php

namespace App\Http\Controllers\Api;

// Import model post dulu
use App\Models\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
//import si storage
use Illuminate\Support\Facades\Storage;
// gunakan validator
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index(){
        //get all posts
        $posts=Post::latest()->paginate(5);

        // kembalikan nilai $posts sebagai resource
        return new PostResource(true, 'List Data Post', $posts);
    }

    public function store(Request $request){
        // buat validator dulu
        $validator = Validator::make($request->all(), [
           'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
           'title' => 'required',
           'content' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // upload image
        $image = $request->file('image');
        $image->storeAs('public/posts', $image->hashName());
        // setelah itu create post
        $post = Post::create([
            'image' => $image->hashName(),
            'title' => $request->title,
            'content' => $request->content
        ]);

        return new PostResource(true, 'Data Post Berhasil Ditambah!', $post);
    }

    public function show($id){
        //find post by id
        $post = Post::find($id);

        if($post){
            return new PostResource(true, 'Data Post Ditemukan!', $post);
        }
        
        return response()->json([
            'success' => false,
            'message' => "Data Tidak Ditemukan!",
            'data' => []
        ], 404);
    }

    public function update(Request $request, $id){
        $validator  = Validator::make($request->all(), [
            'image' => 'required',
            'content' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // dapat post by id
        $post = Post::find($id);

        // cek dulu image klw kosong
       if ($request->hasFile('image')) {
        
        // jika ada replace tambah image baru
        $image = $request->file('image');
        $image->storeAs('public/posts', $image->hashName());

        // lalu delete old image
        Storage::delete('public/posts/'.basename($post->image));

        // lalu update data post dengan image baru
        $post->update([
            'image' => $image->hashName(),
            'title' => $request->title,
            'content' => $request->content
        ]);
       } else {

        // update post tanpa image lama
        $post->update([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

       }
       return new PostResource(true, 'Data Post Berhasil Diupdate!', $post);
    }

    public function destroy($id){
        $post = Post::find($id);

        // hapus image di storage
        Storage::delete('public/posts/'.basename($post->image));

        // lalu delete post
        $post->delete();

        return new PostResource(true, 'Data Post Berhasil Dihapus!', $post);
    }
}

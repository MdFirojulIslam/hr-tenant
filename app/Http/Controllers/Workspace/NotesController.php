<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\note_share;
use App\Models\notes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotesController extends Controller
{
    // public function index()
    // {
    //     $userId = Auth::user()->EID;

    //     $notes = Notes::where(function ($query) use ($userId) {
    //         $query->where('created_by', $userId);
    //     })
    //         ->get()
    //         ->groupBy('Pin');

    //     $sharedNotes = note_share::select(
    //         'notes.id',
    //         'notes.Title',
    //         'notes.Content',
    //         'notes.Pin',
    //         'notes.created_at',
    //         'notes.created_by',
    //         'creator_imgs.img_url as creator_img'
    //     )
    //         ->join('notes', 'notes.id', '=', 'note_shares.Note_Id')
    //         ->join('emp_imgs as creator_imgs', 'notes.created_by', '=', 'creator_imgs.EID')
    //         ->where('note_shares.EID', $userId)
    //         ->whereNot('notes.created_by', $userId)
    //         ->groupBy('notes.id', 'notes.Title', 'notes.Content', 'notes.Pin', 'notes.created_at', 'notes.created_by', 'creator_imgs.img_url')
    //         ->get();

    //     // Fetch shared employees' images without duplication
    //     $sharedUserImages = note_share::select('note_shares.Note_Id', 'note_shares.EID', 'shared_imgs.img_url')
    //         ->join('emp_imgs as shared_imgs', 'note_shares.EID', '=', 'shared_imgs.EID')
    //         ->whereIn('note_shares.Note_Id', $sharedNotes->pluck('id'))
    //         ->distinct()
    //         ->get()
    //         ->groupBy('Note_Id');

    //     // Attach shared images to notes
    //     $sharedNotes->each(function ($note) use ($sharedUserImages) {
    //         $note->shared_user_images = $sharedUserImages[$note->id] ?? collect();
    //     });

    //     return response()->json([
    //         'pinNotes' => $notes[1] ?? collect(),
    //         'unPinNotes' => $notes[0] ?? collect(),
    //         'sharedNotes' => $sharedNotes
    //     ]);
    // }

    public function index()
    {
        $userId = Auth::user()->EID;
        // 1. Fetch notes created by the user
        $notes = Notes::where('created_by', $userId)
            ->get()
            ->groupBy('Pin');

        // 2. Get all your own note IDs
        $ownNoteIds = $notes->flatten()->pluck('id');

        // 3. Fetch all shared images for those notes (notes you created and shared)
        $ownSharedUserImages = note_share::select('note_shares.Note_Id', 'note_shares.EID', 'shared_imgs.img_url')
            ->join('emp_imgs as shared_imgs', 'note_shares.EID', '=', 'shared_imgs.EID')
            ->whereIn('note_shares.Note_Id', $ownNoteIds)
            ->distinct()
            ->get()
            ->groupBy('Note_Id');

        // 4. Attach shared user images to each own note
        $notes = $notes->map(function ($group) use ($ownSharedUserImages) {
            return $group->map(function ($note) use ($ownSharedUserImages) {
                $note->shared_user_images = $ownSharedUserImages[$note->id] ?? collect();
                return $note;
            });
        });

        // 5. Shared notes (notes shared to this user by others)
        $sharedNotes = note_share::select(
            'notes.id',
            'notes.Title',
            'notes.Content',
            'notes.Pin',
            'notes.created_at',
            'notes.created_by',
            'creator_imgs.img_url as creator_img'
        )
            ->join('notes', 'notes.id', '=', 'note_shares.Note_Id')
            ->join('emp_imgs as creator_imgs', 'notes.created_by', '=', 'creator_imgs.EID')
            ->where('note_shares.EID', $userId)
            ->whereNot('notes.created_by', $userId)
            ->groupBy(
                'notes.id',
                'notes.Title',
                'notes.Content',
                'notes.Pin',
                'notes.created_at',
                'notes.created_by',
                'creator_imgs.img_url'
            )
            ->get();

        // 6. Shared images for notes shared to this user
        $sharedUserImages = note_share::select('note_shares.Note_Id', 'note_shares.EID', 'shared_imgs.img_url')
            ->join('emp_imgs as shared_imgs', 'note_shares.EID', '=', 'shared_imgs.EID')
            ->whereIn('note_shares.Note_Id', $sharedNotes->pluck('id'))
            ->distinct()
            ->get()
            ->groupBy('Note_Id');

        // 7. Attach shared images to shared notes
        $sharedNotes->each(function ($note) use ($sharedUserImages) {
            $note->shared_user_images = $sharedUserImages[$note->id] ?? collect();
        });

        // 8. Final response
        return response()->json([
            'pinNotes' => $notes[1] ?? collect(),
            'unPinNotes' => $notes[0] ?? collect(),
            'sharedNotes' => $sharedNotes
        ]);
    }

    public function pinNotes(Request $request)
    {
        $id = $request->query('id');
        $pin = $request->query('pin');

        $note = notes::findOrFail($id);

        $note->update([
            'Pin' => $pin,
        ]);

        return response()->json('Succesfully Pinned!');
    }


    public function store(Request $request)
    {
        $userId = Auth::user()->EID;

        $note = notes::create([
            'Title' => $request->title,
            'Content' => $request->content,
            'created_by' => $userId
        ]);


        $response = [
            'success' => true,
            'message' => 'Note created successfully',
        ];

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $userId = Auth::user()->EID;

        $note = notes::where('id', $id)->first();

        if ($note) {
            $note->update([
                'Title' => $request->title,
                'Content' => $request->content,
                'updated_by' => $userId
            ]);

            $response = [
                'success' => true,
                'message' => 'Note updated successfully',
            ];

            return response()->json($response);
        }
    }


    public function destroy($id)
    {
        $note = notes::find($id);
        if ($note) {
            $note->delete();
        }
        return response()->json(['message' => 'Note deleted successfully']);
    }

    public function trashNote()
    {
        $note = notes::onlyTrashed()->get();
        return response()->json($note);
    }

    public function shareNote(Request $request)
    {
        $noteId = $request->input('note_id');
        $employeeIds = $request->input('emp_ids');

        if (!is_array($employeeIds)) {
            return response()->json(['error' => 'Invalid or missing emp_ids'], 400);
        }

        $idsToSave = $employeeIds;

        DB::table('note_shares')->where('Note_Id', $noteId)->delete();

        foreach ($idsToSave as $eid) {
            note_share::create([
                'EID' => $eid,
                'Note_Id' => $noteId,
            ]);
        }

        return response()->json(['message' => 'Note shares successfully.']);
    }


    public function noteShateIds(Request $request)
    {
        $noteId = $request->input('id');
        $emp_ids = note_share::where('note_id', $noteId)->pluck('EID')->toArray();
        return response()->json($emp_ids);
    }
}

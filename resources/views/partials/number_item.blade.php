@foreach($users as $user)
    <tr id="user_row_{{$user->id}}">{{$user}}
        <td>{{$user->id}}</td>
        <td>{{$user->firstName}}</td>
        <td>{{$user->lastName}}</td>
        <td>{{$user->phone}}</td>
        <td>{{$user->role_id == 2 ? "Tutor" : "Student"}}</td>
        <td><button type='button' onclick="deleteNumber({{$user->id}})" style="background-color: red; color: white">Delete</button></td>
    </tr>
@endforeach
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Audit Results for {{ $auditee->username }}</h2>
    <p>Date: {{ $date }}</p>

    <table>
        <thead>
            <tr>
                <th>Question</th>
                <th>Answer</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($auditResults as $audit)
                @foreach ($audit->questions as $question)
                    <tr>
                        <td>{{ $question->question }}</td>
                        <td>{{ $question->answer == 1 ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    
    <h3>Recommendations:</h3>
    <ul>
        @foreach ($recommendations as $recommendation)
            <li>{{ $recommendation }}</li>
        @endforeach
    </ul>
</body>
</html>

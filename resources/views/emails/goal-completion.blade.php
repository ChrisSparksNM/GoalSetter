<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Completed</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .celebration {
            font-size: 48px;
            margin: 10px 0;
        }
        .goal-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .goal-details h2 {
            color: #495057;
            margin-top: 0;
            font-size: 20px;
        }
        .detail-row {
            margin: 10px 0;
            padding: 8px 0;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            display: inline-block;
            width: 120px;
        }
        .detail-value {
            color: #495057;
        }
        .message {
            text-align: center;
            margin: 30px 0;
            font-size: 16px;
            color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .highlight {
            color: #28a745;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="celebration">🎉</div>
            <h1>Goal Completed!</h1>
        </div>

        <div class="message">
            <p><strong class="highlight">{{ $goal->user->name }}</strong> has successfully completed their goal!</p>
        </div>

        <div class="goal-details">
            <h2>Goal Details</h2>
            
            <div class="detail-row">
                <span class="detail-label">Title:</span>
                <span class="detail-value">{{ $goal->title }}</span>
            </div>
            
            @if($goal->description)
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value">{{ $goal->description }}</span>
            </div>
            @endif
            
            <div class="detail-row">
                <span class="detail-label">Target Date:</span>
                <span class="detail-value">{{ $goal->end_date->format('F j, Y') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Completed:</span>
                <span class="detail-value highlight">{{ $goal->completed_at->format('F j, Y \a\t g:i A') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">User:</span>
                <span class="detail-value">{{ $goal->user->name }} ({{ $goal->user->email }})</span>
            </div>
        </div>

        <div class="message">
            <p>Congratulations to {{ $goal->user->name }} on this achievement! 🌟</p>
        </div>

        <div class="footer">
            <p>This notification was sent automatically from the Goal Management System.</p>
            <p>Sent on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html>

<head>
  <title>Itinerary Submitted</title>
</head>

<body>
  <h3>New Itinerary Submitted</h3>

  <p><strong>Employee:</strong> {{ $senderName }}</p>
  <p><strong>Designation:</strong> {{ $senderDesignation }}</p>
  <p>
    <strong>Itinerary Date:</strong>
    {{ \Carbon\Carbon::parse($itineraryFor)->format('F Y') }}
  </p>

  <p>Please review the itinerary.</p>

  <br>
</body>

</html>

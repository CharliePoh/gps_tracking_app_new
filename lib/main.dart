import 'package:flutter/material.dart';
import 'package:location/location.dart' as location;
import 'package:permission_handler/permission_handler.dart'
    as permission_handler;
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'dart:math';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'GPS Tracking App',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: GPSPage(),
    );
  }
}

class GPSPage extends StatefulWidget {
  @override
  _GPSPageState createState() => _GPSPageState();
}

class _GPSPageState extends State<GPSPage> {
  bool isTracking = false;
  String locationMessage = "";
  late location.Location _location;
  bool _isTrackingEnabled = false;
  double? lastLatitude;
  double? lastLongitude;

  @override
  void initState() {
    super.initState();
    _location = location.Location();
    checkPermissions();
  }

  // Check and request location permissions
  Future<void> checkPermissions() async {
    var status =
        await permission_handler.Permission.locationWhenInUse.request();
    var backgroundStatus =
        await permission_handler.Permission.locationAlways.request();

    if (status.isGranted && backgroundStatus.isGranted) {
      print("Location permissions granted");
    } else if (status.isDenied || backgroundStatus.isDenied) {
      print("Location permissions denied");
    } else if (status.isPermanentlyDenied ||
        backgroundStatus.isPermanentlyDenied) {
      openAppSettings();
    }
  }

  // Open app settings if permission is permanently denied
  Future<void> openAppSettings() async {
    await permission_handler.openAppSettings();
  }

  // Calculate distance between two coordinates (Haversine formula)
  double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const double earthRadius = 6371000; // Earth's radius in meters
    final double dLat = (lat2 - lat1) * pi / 180;
    final double dLon = (lon2 - lon1) * pi / 180;

    final double a = sin(dLat / 2) * sin(dLat / 2) +
        cos(lat1 * pi / 180) *
            cos(lat2 * pi / 180) *
            sin(dLon / 2) *
            sin(dLon / 2);
    final double c = 2 * atan2(sqrt(a), sqrt(1 - a));

    return earthRadius * c;
  }

  // Start tracking the location
  Future<void> startTracking() async {
    bool serviceEnabled = await _location.serviceEnabled();
    if (!serviceEnabled) {
      serviceEnabled = await _location.requestService();
      if (!serviceEnabled) {
        print("Location services are disabled.");
        return;
      }
    }

    location.PermissionStatus permissionGranted =
        await _location.hasPermission();
    if (permissionGranted == location.PermissionStatus.denied) {
      permissionGranted = await _location.requestPermission();
      if (permissionGranted != location.PermissionStatus.granted) {
        print("Location permission denied");
        return;
      }
    }

    // Enable tracking
    _isTrackingEnabled = true;

    // Get the current location immediately
    try {
      final currentLocation = await _location.getLocation();
      if (_isTrackingEnabled &&
          currentLocation.latitude != null &&
          currentLocation.longitude != null) {
        storeLocationIfNecessary(
            currentLocation.latitude!, currentLocation.longitude!);
      }
    } catch (e) {
      print("Error fetching current location: $e");
    }

    // Listen to location changes for continuous tracking
    _location.onLocationChanged
        .listen((location.LocationData updatedLocation) async {
      if (_isTrackingEnabled &&
          updatedLocation.latitude != null &&
          updatedLocation.longitude != null) {
        storeLocationIfNecessary(
            updatedLocation.latitude!, updatedLocation.longitude!);
      }
    });
  }

  // Stop tracking the location
  void stopTracking() {
    setState(() {
      _isTrackingEnabled = false; // Disable data storage
      locationMessage = "Tracking stopped";

      // Reset the last stored location
      lastLatitude = null;
      lastLongitude = null;
    });
  }

  // Send location data to the server if necessary
  Future<void> storeLocationIfNecessary(
      double latitude, double longitude) async {
    const double distanceThreshold = 1.0; // 1 meter
    bool shouldStore = false;

    // Case 1: No previous location, store immediately
    if (lastLatitude == null || lastLongitude == null) {
      shouldStore = true;
    } else {
      // Case 2: Calculate the distance from the last stored location
      final distance =
          calculateDistance(lastLatitude!, lastLongitude!, latitude, longitude);
      if (distance > distanceThreshold) {
        shouldStore = true;
      }
    }

    // Store the location if necessary
    if (shouldStore) {
      lastLatitude = latitude;
      lastLongitude = longitude;

      setState(() {
        locationMessage = "Latitude: $latitude, Longitude: $longitude";
      });

      // Call the API to store location
      await storeLocation("userId", latitude, longitude);
    }
  }

  // Send location data to the server
  Future<void> storeLocation(
      String userId, double latitude, double longitude) async {
    const String url =
        "http://10.0.2.2/gps_tracking_app_new/web/php/insert_location.php"; // Update for the emulator

    try {
      final response = await http.post(
        Uri.parse(url),
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({
          "user_id": userId,
          "latitude": latitude,
          "longitude": longitude,
        }),
      );

      if (response.statusCode == 200) {
        print("Data inserted successfully.");
      } else {
        print("Server error: ${response.statusCode}");
      }
    } catch (e) {
      print("Error: $e");
    }
  }

  // Toggle tracking status
  void toggleTracking() {
    setState(() {
      isTracking = !isTracking;
    });

    if (isTracking) {
      // Start tracking when toggled on
      startTracking();
    } else {
      // Stop tracking when toggled off
      stopTracking();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("GPS Tracking App"),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            ElevatedButton(
              onPressed: toggleTracking,
              child: Text(isTracking ? 'Stop Tracking' : 'Start Tracking'),
            ),
            SizedBox(height: 20),
            Text(
              locationMessage.isEmpty ? "Tracking not active" : locationMessage,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 18),
            ),
          ],
        ),
      ),
    );
  }
}

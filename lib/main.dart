import 'package:flutter/material.dart';
import 'package:location/location.dart' as location; // Alias 'location' package
import 'package:permission_handler/permission_handler.dart'
    as permission_handler; // Alias 'permission_handler' package

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

  @override
  void initState() {
    super.initState();
    _location = location.Location();
    checkPermissions(); // Ensure this is after super.initState()
  }

  // Check and request location permissions
  Future<void> checkPermissions() async {
    // Request location permissions for both foreground and background
    var status =
        await permission_handler.Permission.locationWhenInUse.request();
    var backgroundStatus =
        await permission_handler.Permission.locationAlways.request();

    // Handle permission responses
    if (status.isGranted && backgroundStatus.isGranted) {
      print("Location permissions granted");
    } else if (status.isDenied || backgroundStatus.isDenied) {
      print("Location permissions denied");
    } else if (status.isPermanentlyDenied ||
        backgroundStatus.isPermanentlyDenied) {
      openAppSettings(); // Open app settings if permission is permanently denied
    }
  }

  // Open app settings if permission is permanently denied
  Future<void> openAppSettings() async {
    await permission_handler.openAppSettings();
  }

  // Start tracking the location
  Future<void> startTracking() async {
    // Check if location services are enabled
    bool serviceEnabled = await _location.serviceEnabled();
    if (!serviceEnabled) {
      serviceEnabled = await _location.requestService();
      if (!serviceEnabled) {
        print("Location services are disabled.");
        return;
      }
    }

    // Check and request location permission
    location.PermissionStatus permissionGranted =
        await _location.hasPermission();
    if (permissionGranted == location.PermissionStatus.denied) {
      permissionGranted = await _location.requestPermission();
      if (permissionGranted != location.PermissionStatus.granted) {
        // If permission denied
        print("Location permission denied");
        return;
      }
    }

    // Start listening to location changes
    _location.onLocationChanged.listen((location.LocationData currentLocation) {
      setState(() {
        locationMessage =
            "Latitude: ${currentLocation.latitude}, Longitude: ${currentLocation.longitude}";
      });
    });
  }

  // Toggle tracking status
  void toggleTracking() {
    setState(() {
      isTracking = !isTracking;
    });

    if (isTracking) {
      startTracking();
    } else {
      setState(() {
        locationMessage = "";
      });
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

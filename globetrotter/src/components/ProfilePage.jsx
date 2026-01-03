// src/components/ProfilePage.jsx
import React, { useState } from 'react';
import { 
  Edit3, 
  Settings, 
  MapPin, 
  Calendar, 
  Mail, 
  Phone, 
  Globe, 
  Users, 
  Heart, 
  Star, 
  Camera,
  Plus,
  ChevronRight,
  Briefcase,
  Award,
  Share2,
  Download,
  Trash2,
  Lock,
  Bell
} from 'lucide-react';

const ProfilePage = () => {
  const [isEditing, setIsEditing] = useState(false);
  const [activeTab, setActiveTab] = useState('trips');

  // User data
  const userData = {
    name: 'Alex Johnson',
    username: '@alexjohnson',
    bio: 'Passionate traveler exploring the world, one adventure at a time. Love photography, local cuisine, and off-the-beaten-path experiences.',
    location: 'San Francisco, CA',
    joinDate: 'Joined March 2024',
    email: 'alex.johnson@email.com',
    phone: '+1 (555) 123-4567',
    website: 'www.alexjohnson.com',
    followers: '1.2k',
    following: '568',
    tripsCompleted: 24,
    countriesVisited: 15,
    badges: 8
  };

  // Preplanned trips data
  const preplannedTrips = [
    {
      id: 1,
      title: 'Japan Cherry Blossom',
      date: 'Mar 15 - Mar 30, 2026',
      location: 'Tokyo, Kyoto, Osaka',
      travelers: 3,
      budget: '$4,200',
      image: 'https://images.unsplash.com/photo-1528164344705-47542687000d?w=800&q=80',
      progress: 75
    },
    {
      id: 2,
      title: 'European Summer',
      date: 'Jun 10 - Jul 5, 2026',
      location: 'Paris, Rome, Barcelona',
      travelers: 2,
      budget: '$5,800',
      image: 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=800&q=80',
      progress: 45
    },
    {
      id: 3,
      title: 'Ski Trip Alps',
      date: 'Dec 20 - Dec 29, 2026',
      location: 'Swiss Alps, Austria',
      travelers: 4,
      budget: '$3,500',
      image: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=800&q=80',
      progress: 20
    }
  ];

  // Previous trips data
  const previousTrips = [
    {
      id: 1,
      title: 'Bali Retreat',
      date: 'Nov 5 - Nov 15, 2025',
      location: 'Bali, Indonesia',
      travelers: 2,
      rating: 4.8,
      image: 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?w=800&q=80',
      highlights: ['Beaches', 'Temples', 'Surfing']
    },
    {
      id: 2,
      title: 'New York City',
      date: 'Aug 20 - Aug 27, 2025',
      location: 'New York, USA',
      travelers: 1,
      rating: 4.5,
      image: 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800&q=80',
      highlights: ['Broadway', 'Museums', 'Shopping']
    },
    {
      id: 3,
      title: 'Greek Islands',
      date: 'May 10 - May 20, 2025',
      location: 'Santorini, Mykonos',
      travelers: 2,
      rating: 4.9,
      image: 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=800&q=80',
      highlights: ['Sunsets', 'Beaches', 'History']
    }
  ];

  // Attractions/Interests
  const interests = [
    { id: 1, name: 'Adventure Sports', icon: '⛰️', color: 'bg-orange-100 text-orange-800' },
    { id: 2, name: 'Photography', icon: '📷', color: 'bg-blue-100 text-blue-800' },
    { id: 3, name: 'Local Cuisine', icon: '🍜', color: 'bg-red-100 text-red-800' },
    { id: 4, name: 'Historical Sites', icon: '🏛️', color: 'bg-amber-100 text-amber-800' },
    { id: 5, name: 'Beach Vacations', icon: '🏖️', color: 'bg-cyan-100 text-cyan-800' },
    { id: 6, name: 'Hiking', icon: '🥾', color: 'bg-green-100 text-green-800' },
    { id: 7, name: 'City Exploration', icon: '🏙️', color: 'bg-purple-100 text-purple-800' },
    { id: 8, name: 'Wildlife', icon: '🐘', color: 'bg-emerald-100 text-emerald-800' }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      {/* Top Navigation */}
      <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <h1 className="text-2xl font-bold text-gray-900">GlobalTrotter</h1>
              <div className="ml-8 flex space-x-6">
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                <a href="#" className="text-blue-600 font-semibold border-b-2 border-blue-600 pb-1">Profile</a>
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Trips</a>
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Explore</a>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <button className="p-2 hover:bg-gray-100 rounded-full">
                <Bell className="h-5 w-5 text-gray-600" />
              </button>
              <button className="p-2 hover:bg-gray-100 rounded-full">
                <Settings className="h-5 w-5 text-gray-600" />
              </button>
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Profile Header */}
        <div className="relative mb-8">
          {/* Cover Photo */}
          <div className="h-64 rounded-2xl overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600">
            <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            <button className="absolute top-4 right-4 bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg hover:bg-white/30 transition-colors">
              Edit Cover
            </button>
          </div>

          {/* Profile Info */}
          <div className="relative -mt-16 px-8">
            <div className="flex flex-col md:flex-row items-start md:items-end justify-between">
              <div className="flex items-end">
                {/* Profile Picture */}
                <div className="relative">
                  <div className="w-32 h-32 rounded-full border-4 border-white bg-gradient-to-br from-blue-400 to-purple-500 shadow-lg flex items-center justify-center">
                    <span className="text-white text-4xl font-bold">AJ</span>
                  </div>
                  <button className="absolute bottom-2 right-2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors shadow-lg">
                    <Camera className="h-4 w-4" />
                  </button>
                </div>

                {/* User Info */}
                <div className="ml-6 mb-4">
                  <div className="flex items-center space-x-4">
                    <h1 className="text-3xl font-bold text-gray-900">{userData.name}</h1>
                    <span className="text-gray-600">{userData.username}</span>
                  </div>
                  <p className="text-gray-700 mt-2 max-w-2xl">{userData.bio}</p>
                  <div className="flex flex-wrap items-center gap-4 mt-4">
                    <div className="flex items-center text-gray-600">
                      <MapPin className="h-4 w-4 mr-2" />
                      {userData.location}
                    </div>
                    <div className="flex items-center text-gray-600">
                      <Calendar className="h-4 w-4 mr-2" />
                      {userData.joinDate}
                    </div>
                  </div>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex flex-wrap gap-3 mt-4 md:mt-0">
                <button 
                  onClick={() => setIsEditing(!isEditing)}
                  className="flex items-center px-6 py-3 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                >
                  <Edit3 className="h-5 w-5 mr-2" />
                  {isEditing ? 'Save Changes' : 'Edit Profile'}
                </button>
                <button className="flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                  <Share2 className="h-5 w-5 mr-2" />
                  Share Profile
                </button>
                <button className="p-3 border border-gray-300 rounded-xl hover:bg-gray-50">
                  <Settings className="h-5 w-5 text-gray-600" />
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <div className="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
            <div className="flex items-center">
              <div className="p-3 bg-blue-100 rounded-xl">
                <Globe className="h-6 w-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-500">Countries Visited</p>
                <p className="text-2xl font-bold text-gray-900">{userData.countriesVisited}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
            <div className="flex items-center">
              <div className="p-3 bg-green-100 rounded-xl">
                <Briefcase className="h-6 w-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-500">Trips Completed</p>
                <p className="text-2xl font-bold text-gray-900">{userData.tripsCompleted}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
            <div className="flex items-center">
              <div className="p-3 bg-purple-100 rounded-xl">
                <Users className="h-6 w-6 text-purple-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-500">Followers</p>
                <p className="text-2xl font-bold text-gray-900">{userData.followers}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
            <div className="flex items-center">
              <div className="p-3 bg-yellow-100 rounded-xl">
                <Award className="h-6 w-6 text-yellow-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm text-gray-500">Badges Earned</p>
                <p className="text-2xl font-bold text-gray-900">{userData.badges}</p>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column - User Details */}
          <div className="lg:col-span-2 space-y-8">
            {/* Profile Tabs */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
              <div className="border-b border-gray-200">
                <nav className="flex space-x-8 px-6">
                  <button
                    onClick={() => setActiveTab('trips')}
                    className={`py-4 font-medium border-b-2 transition-colors ${
                      activeTab === 'trips'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    Trips & Adventures
                  </button>
                  <button
                    onClick={() => setActiveTab('details')}
                    className={`py-4 font-medium border-b-2 transition-colors ${
                      activeTab === 'details'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    Personal Details
                  </button>
                  <button
                    onClick={() => setActiveTab('interests')}
                    className={`py-4 font-medium border-b-2 transition-colors ${
                      activeTab === 'interests'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    Interests & Preferences
                  </button>
                </nav>
              </div>

              {/* Tab Content */}
              <div className="p-6">
                {activeTab === 'trips' && (
                  <div className="space-y-8">
                    {/* Preplanned Trips Section */}
                    <section>
                      <div className="flex justify-between items-center mb-6">
                        <div>
                          <h2 className="text-2xl font-bold text-gray-900">Preplanned Trips</h2>
                          <p className="text-gray-600">Upcoming adventures you're planning</p>
                        </div>
                        <button className="text-blue-600 hover:text-blue-700 font-medium">
                          View All →
                        </button>
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {preplannedTrips.map(trip => (
                          <div key={trip.id} className="group">
                            <div className="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all">
                              <div className="relative h-40 overflow-hidden">
                                <img 
                                  src={trip.image} 
                                  alt={trip.title}
                                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                                <div className="absolute bottom-4 left-4">
                                  <div className="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-lg">
                                    <span className="text-sm font-semibold text-gray-900">{trip.progress}% planned</span>
                                  </div>
                                </div>
                              </div>
                              
                              <div className="p-4">
                                <h3 className="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                  {trip.title}
                                </h3>
                                <div className="flex items-center text-gray-600 text-sm mt-2">
                                  <Calendar className="h-4 w-4 mr-2" />
                                  {trip.date}
                                </div>
                                <div className="flex items-center justify-between mt-4">
                                  <span className="text-gray-700 font-medium">{trip.budget}</span>
                                  <button className="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    View Details →
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </section>

                    {/* Previous Trips Section */}
                    <section>
                      <div className="flex justify-between items-center mb-6">
                        <div>
                          <h2 className="text-2xl font-bold text-gray-900">Previous Trips</h2>
                          <p className="text-gray-600">Memories from past adventures</p>
                        </div>
                        <button className="text-blue-600 hover:text-blue-700 font-medium">
                          View All →
                        </button>
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {previousTrips.map(trip => (
                          <div key={trip.id} className="group">
                            <div className="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all">
                              <div className="relative h-40 overflow-hidden">
                                <img 
                                  src={trip.image} 
                                  alt={trip.title}
                                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                                <div className="absolute top-4 right-4 flex items-center bg-white/90 backdrop-blur-sm px-3 py-1 rounded-lg">
                                  <Star className="h-4 w-4 text-yellow-500 fill-current" />
                                  <span className="ml-1 text-sm font-semibold">{trip.rating}</span>
                                </div>
                              </div>
                              
                              <div className="p-4">
                                <h3 className="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                  {trip.title}
                                </h3>
                                <div className="flex items-center text-gray-600 text-sm mt-2">
                                  <MapPin className="h-4 w-4 mr-2" />
                                  {trip.location}
                                </div>
                                <div className="flex flex-wrap gap-2 mt-4">
                                  {trip.highlights.map((highlight, idx) => (
                                    <span key={idx} className="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                      {highlight}
                                    </span>
                                  ))}
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </section>
                  </div>
                )}

                {activeTab === 'details' && (
                  <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="space-y-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                          <div className="flex items-center p-3 border border-gray-300 rounded-lg bg-gray-50">
                            <span className="text-gray-900">{userData.name}</span>
                          </div>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                          <div className="flex items-center p-3 border border-gray-300 rounded-lg bg-gray-50">
                            <Mail className="h-5 w-5 text-gray-400 mr-3" />
                            <span className="text-gray-900">{userData.email}</span>
                          </div>
                        </div>
                      </div>
                      <div className="space-y-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                          <div className="flex items-center p-3 border border-gray-300 rounded-lg bg-gray-50">
                            <Phone className="h-5 w-5 text-gray-400 mr-3" />
                            <span className="text-gray-900">{userData.phone}</span>
                          </div>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">Website</label>
                          <div className="flex items-center p-3 border border-gray-300 rounded-lg bg-gray-50">
                            <Globe className="h-5 w-5 text-gray-400 mr-3" />
                            <span className="text-gray-900">{userData.website}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div className="pt-6 border-t border-gray-200">
                      <div className="flex justify-between items-center">
                        <h3 className="font-medium text-gray-900">Security & Privacy</h3>
                        <button className="flex items-center text-blue-600 hover:text-blue-700">
                          <Lock className="h-4 w-4 mr-2" />
                          Change Password
                        </button>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === 'interests' && (
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Travel Interests</h3>
                    <div className="flex flex-wrap gap-3 mb-8">
                      {interests.map(interest => (
                        <div
                          key={interest.id}
                          className={`px-4 py-3 rounded-xl ${interest.color} flex items-center`}
                        >
                          <span className="mr-2">{interest.icon}</span>
                          <span className="font-medium">{interest.name}</span>
                        </div>
                      ))}
                    </div>
                    
                    <div className="border-t border-gray-200 pt-6">
                      <h3 className="text-lg font-semibold text-gray-900 mb-4">Travel Preferences</h3>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-4">
                          <div className="flex justify-between items-center">
                            <span className="text-gray-700">Flight Class</span>
                            <span className="font-medium text-gray-900">Economy Premium</span>
                          </div>
                          <div className="flex justify-between items-center">
                            <span className="text-gray-700">Accommodation</span>
                            <span className="font-medium text-gray-900">Hotels & Boutique</span>
                          </div>
                        </div>
                        <div className="space-y-4">
                          <div className="flex justify-between items-center">
                            <span className="text-gray-700">Travel Pace</span>
                            <span className="font-medium text-gray-900">Moderate</span>
                          </div>
                          <div className="flex justify-between items-center">
                            <span className="text-gray-700">Dietary Preference</span>
                            <span className="font-medium text-gray-900">Flexitarian</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Right Column - Additional Info */}
          <div className="space-y-6">
            {/* Contact Info Card */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h3 className="font-bold text-gray-900 mb-4">Contact Information</h3>
              <div className="space-y-4">
                <div className="flex items-center text-gray-700">
                  <Mail className="h-5 w-5 text-gray-400 mr-3" />
                  <span>{userData.email}</span>
                </div>
                <div className="flex items-center text-gray-700">
                  <Phone className="h-5 w-5 text-gray-400 mr-3" />
                  <span>{userData.phone}</span>
                </div>
                <div className="flex items-center text-gray-700">
                  <Globe className="h-5 w-5 text-gray-400 mr-3" />
                  <span>{userData.website}</span>
                </div>
              </div>
              <button className="w-full mt-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Update Contact Info
              </button>
            </div>

            {/* Quick Actions Card */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h3 className="font-bold text-gray-900 mb-4">Quick Actions</h3>
              <div className="space-y-3">
                <button className="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                  <span className="text-gray-700">Download Travel Data</span>
                  <Download className="h-4 w-4 text-gray-400" />
                </button>
                <button className="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                  <span className="text-gray-700">Privacy Settings</span>
                  <Lock className="h-4 w-4 text-gray-400" />
                </button>
                <button className="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-red-600">
                  <span>Delete Account</span>
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            </div>

            {/* Recent Activity */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
              <h3 className="font-bold text-gray-900 mb-4">Recent Activity</h3>
              <div className="space-y-4">
                <div className="flex items-start">
                  <div className="p-2 bg-blue-100 rounded-lg mr-3">
                    <Heart className="h-4 w-4 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-900">Liked "Best Beaches in Thailand"</p>
                    <p className="text-xs text-gray-500">2 hours ago</p>
                  </div>
                </div>
                <div className="flex items-start">
                  <div className="p-2 bg-green-100 rounded-lg mr-3">
                    <Star className="h-4 w-4 text-green-600" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-900">Rated Bali trip 4.8 stars</p>
                    <p className="text-xs text-gray-500">1 day ago</p>
                  </div>
                </div>
                <div className="flex items-start">
                  <div className="p-2 bg-purple-100 rounded-lg mr-3">
                    <Plus className="h-4 w-4 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-900">Added new trip to Japan</p>
                    <p className="text-xs text-gray-500">3 days ago</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default ProfilePage;
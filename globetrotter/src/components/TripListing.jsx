// src/components/TripListing.jsx
import React, { useState } from 'react';
import { 
  Search, 
  Filter, 
  Calendar, 
  MapPin, 
  Users, 
  ChevronRight,
  MoreVertical,
  Grid,
  List,
  Plus,
  Plane,
  Clock,
  CheckCircle,
  Star,
  TrendingUp
} from 'lucide-react';

const TripListing = () => {
  const [viewMode, setViewMode] = useState('grid');
  const [activeFilter, setActiveFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Sample trip data
  const trips = {
    ongoing: [
      { 
        id: 1, 
        title: 'Tokyo Adventure', 
        date: 'Jan 3 - Jan 10, 2026', 
        location: 'Tokyo, Japan', 
        travelers: 3,
        progress: 65,
        budget: '$2,450',
        image: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80',
        daysLeft: 4,
        highlights: ['Sushi Making', 'Mt. Fuji Tour', 'Akihabara']
      }
    ],
    upcoming: [
      { 
        id: 2, 
        title: 'Paris Getaway', 
        date: 'Jan 15 - Jan 22, 2026', 
        location: 'Paris, France', 
        travelers: 2,
        budget: '$3,200',
        image: 'https://images.unsplash.com/photo-1502602897457-92c8ce06ef5d?w=800&q=80',
        daysUntil: 12,
        highlights: ['Eiffel Tower', 'Louvre', 'Seine Cruise']
      },
      { 
        id: 3, 
        title: 'Bali Retreat', 
        date: 'Feb 5 - Feb 12, 2026', 
        location: 'Bali, Indonesia', 
        travelers: 4,
        budget: '$1,800',
        image: 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?w=800&q=80',
        daysUntil: 33,
        highlights: ['Beach Villas', 'Temple Tour', 'Surfing']
      }
    ],
    completed: [
      { 
        id: 4, 
        title: 'New York City Trip', 
        date: 'Nov 20 - Nov 25, 2025', 
        location: 'New York, USA', 
        travelers: 1,
        rating: 4.5,
        image: 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800&q=80',
        highlights: ['Broadway Show', 'Central Park', 'Statue of Liberty']
      },
      { 
        id: 5, 
        title: 'Rome Exploration', 
        date: 'Oct 10 - Oct 17, 2025', 
        location: 'Rome, Italy', 
        travelers: 2,
        rating: 5,
        image: 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=800&q=80',
        highlights: ['Colosseum', 'Vatican', 'Pasta Class']
      }
    ]
  };

  const filters = [
    { id: 'all', label: 'All Trips', count: 5 },
    { id: 'ongoing', label: 'Ongoing', count: 1 },
    { id: 'upcoming', label: 'Upcoming', count: 2 },
    { id: 'completed', label: 'Completed', count: 2 },
    { id: 'international', label: 'International', count: 4 },
    { id: 'family', label: 'Family', count: 2 }
  ];

  const stats = [
    {
      id: 1,
      label: 'Total Trips',
      value: '5',
      icon: Plane,
      color: 'bg-blue-100',
      iconColor: 'text-blue-600'
    },
    {
      id: 2,
      label: 'Ongoing',
      value: '1',
      icon: Clock,
      color: 'bg-green-100',
      iconColor: 'text-green-600'
    },
    {
      id: 3,
      label: 'Upcoming',
      value: '2',
      icon: Calendar,
      color: 'bg-yellow-100',
      iconColor: 'text-yellow-600'
    },
    {
      id: 4,
      label: 'Completed',
      value: '2',
      icon: CheckCircle,
      color: 'bg-purple-100',
      iconColor: 'text-purple-600'
    }
  ];

  // Trip Card Component
  const TripCard = ({ trip, type }) => {
    const getStatusColor = () => {
      switch (type) {
        case 'ongoing': return 'from-green-500 to-emerald-500';
        case 'upcoming': return 'from-blue-500 to-cyan-500';
        case 'completed': return 'from-purple-500 to-pink-500';
        default: return 'from-gray-500 to-gray-600';
      }
    };

    return (
      <div className="group">
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          {/* Image Section */}
          <div className="relative h-48 overflow-hidden">
            <img 
              src={trip.image} 
              alt={trip.title}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            
            {/* Status Badge */}
            <div className="absolute top-4 right-4">
              <span className={`px-3 py-1 bg-gradient-to-r ${getStatusColor()} text-white text-xs font-semibold rounded-full`}>
                {type === 'ongoing' ? 'ONGOING' : type === 'upcoming' ? 'UPCOMING' : 'COMPLETED'}
              </span>
            </div>

            {/* Extra Info Badges */}
            {type === 'ongoing' && trip.daysLeft && (
              <div className="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                <span className="text-sm font-bold text-gray-900">{trip.daysLeft} days left</span>
              </div>
            )}
            {type === 'upcoming' && trip.daysUntil && (
              <div className="absolute bottom-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                <span className="text-sm font-bold text-gray-900">In {trip.daysUntil} days</span>
              </div>
            )}
            {type === 'completed' && trip.rating && (
              <div className="absolute bottom-4 left-4 flex items-center bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                <Star className="h-4 w-4 text-yellow-500 fill-current" />
                <span className="ml-1.5 text-sm font-bold text-gray-900">{trip.rating}</span>
              </div>
            )}
          </div>

          {/* Content Section */}
          <div className="p-5">
            {/* Header */}
            <div className="flex justify-between items-start mb-3">
              <div>
                <h3 className="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1">
                  {trip.title}
                </h3>
                <p className="text-gray-600 text-sm mt-1">{trip.date}</p>
              </div>
              <button className="p-2 hover:bg-gray-100 rounded-lg">
                <MoreVertical className="h-5 w-5 text-gray-400" />
              </button>
            </div>

            {/* Trip Details */}
            <div className="space-y-2 mb-4">
              <div className="flex items-center text-gray-600">
                <MapPin className="h-4 w-4 mr-2 flex-shrink-0" />
                <span className="text-sm line-clamp-1">{trip.location}</span>
              </div>
              <div className="flex items-center text-gray-600">
                <Users className="h-4 w-4 mr-2 flex-shrink-0" />
                <span className="text-sm">{trip.travelers} traveler{trip.travelers !== 1 ? 's' : ''}</span>
              </div>
              {trip.budget && (
                <div className="flex items-center text-gray-600">
                  <span className="text-sm font-semibold text-gray-900">{trip.budget}</span>
                </div>
              )}
            </div>

            {/* Progress Bar for Ongoing Trips */}
            {type === 'ongoing' && trip.progress && (
              <div className="mb-4">
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-gray-600">Progress</span>
                  <span className="text-xs font-semibold text-gray-900">{trip.progress}%</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    className={`bg-gradient-to-r ${getStatusColor()} h-2 rounded-full transition-all duration-500`}
                    style={{ width: `${trip.progress}%` }}
                  ></div>
                </div>
              </div>
            )}

            {/* Highlights */}
            <div className="flex flex-wrap gap-2 mb-4">
              {trip.highlights.slice(0, 2).map((highlight, idx) => (
                <span key={idx} className="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full line-clamp-1">
                  {highlight}
                </span>
              ))}
              {trip.highlights.length > 2 && (
                <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                  +{trip.highlights.length - 2} more
                </span>
              )}
            </div>

            {/* Action Button */}
            <button className="w-full flex items-center justify-center text-blue-600 hover:text-blue-700 font-medium py-2.5 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors group">
              View Details
              <ChevronRight className="h-4 w-4 ml-1 group-hover:translate-x-1 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    );
  };

  // Trip Section Component
  const TripSection = ({ 
    title, 
    subtitle, 
    trips, 
    type, 
    icon, 
    iconColor,
    showLiveBadge = false,
    showCount = false,
    showViewAll = false 
  }) => {
    const Icon = icon;

    return (
      <section>
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center">
            <div className={`p-2 bg-gradient-to-r ${iconColor} rounded-lg`}>
              <Icon className="h-5 w-5 text-white" />
            </div>
            <div className="ml-3">
              <h2 className="text-2xl font-bold text-gray-900">{title}</h2>
              <p className="text-gray-600 text-sm">{subtitle}</p>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            {showLiveBadge && (
              <div className="flex items-center text-sm text-gray-500">
                <span className="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Live now
              </div>
            )}
            {showCount && (
              <span className="text-blue-600 text-sm font-medium">{trips.length} upcoming</span>
            )}
            {showViewAll && (
              <button className="text-gray-600 hover:text-gray-900 text-sm font-medium">
                View all →
              </button>
            )}
          </div>
        </div>
        
        {trips.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {trips.map(trip => (
              <TripCard key={trip.id} trip={trip} type={type} />
            ))}
          </div>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
            <p className="text-gray-500">No {title.toLowerCase()} trips found</p>
          </div>
        )}
      </section>
    );
  };

  return (
    <div className="w-full max-w-7xl mx-auto">
      {/* Page Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Your Travel Adventures</h1>
        <p className="text-gray-600">Manage and explore all your trips in one place</p>
      </div>

      {/* Stats Bar */}
      <div className="mb-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {stats.map((stat) => {
            const Icon = stat.icon;
            return (
              <div key={stat.id} className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div className="flex items-center">
                  <div className={`p-3 ${stat.color} rounded-xl`}>
                    <Icon className={`h-6 w-6 ${stat.iconColor}`} />
                  </div>
                  <div className="ml-4">
                    <p className="text-sm text-gray-500">{stat.label}</p>
                    <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Search and Filter Bar */}
      <div className="mb-10">
        {/* Search Bar */}
        <div className="relative mb-6">
          <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search destinations, dates, or activities..."
            className="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all shadow-sm hover:shadow-md"
          />
        </div>

        {/* Filter Chips */}
        <div className="flex flex-wrap gap-2 mb-6">
          {filters.map(filter => (
            <button
              key={filter.id}
              onClick={() => setActiveFilter(filter.id)}
              className={`px-4 py-2.5 rounded-xl font-medium transition-all ${
                activeFilter === filter.id
                  ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg'
                  : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:shadow-md'
              }`}
            >
              {filter.label}
              <span className={`ml-2 px-2 py-0.5 rounded-full text-xs ${
                activeFilter === filter.id
                  ? 'bg-white/20 text-white'
                  : 'bg-gray-100 text-gray-600'
              }`}>
                {filter.count}
              </span>
            </button>
          ))}
        </div>

        {/* Control Bar */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-4 bg-white rounded-2xl border border-gray-200 shadow-sm">
          <div className="flex flex-wrap items-center gap-4">
            <div className="relative">
              <select className="appearance-none bg-white pl-4 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition min-w-[160px]">
                <option>Group by Status</option>
                <option>Group by Date</option>
                <option>Group by Location</option>
                <option>Group by Budget</option>
              </select>
              <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>

            <button className="flex items-center px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              <Filter className="h-5 w-5 mr-2" />
              Filters
            </button>

            <div className="relative">
              <select className="appearance-none bg-white pl-4 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition min-w-[160px]">
                <option>Sort by: Date</option>
                <option>Sort by: Name</option>
                <option>Sort by: Budget</option>
                <option>Sort by: Travelers</option>
              </select>
            </div>
          </div>

          {/* View Toggle */}
          <div className="flex bg-gray-100 p-1 rounded-lg">
            <button
              onClick={() => setViewMode('grid')}
              className={`px-4 py-2 rounded-md transition-all ${
                viewMode === 'grid'
                  ? 'bg-white shadow-sm text-blue-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <Grid className="h-5 w-5" />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`px-4 py-2 rounded-md transition-all ${
                viewMode === 'list'
                  ? 'bg-white shadow-sm text-blue-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              <List className="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      {/* Create New Trip Button */}
      <div className="flex justify-end mb-8">
        <button className="inline-flex items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl active:scale-[0.98]">
          <Plus className="h-5 w-5 mr-2" />
          Plan New Trip
        </button>
      </div>

      {/* Trip Sections */}
      <div className="space-y-12">
        <TripSection
          title="Ongoing Trips"
          subtitle="Trips you're currently on"
          trips={trips.ongoing}
          type="ongoing"
          iconColor="from-green-500 to-emerald-500"
          icon={Clock}
          showLiveBadge={true}
        />
        
        <TripSection
          title="Upcoming Trips"
          subtitle="Your future adventures"
          trips={trips.upcoming}
          type="upcoming"
          iconColor="from-blue-500 to-cyan-500"
          icon={Calendar}
          showCount={true}
        />
        
        <TripSection
          title="Completed Trips"
          subtitle="Your past adventures"
          trips={trips.completed}
          type="completed"
          iconColor="from-purple-500 to-pink-500"
          icon={CheckCircle}
          showViewAll={true}
        />
      </div>

      {/* Empty State */}
      {Object.values(trips).every(arr => arr.length === 0) && (
        <div className="text-center py-20">
          <div className="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-6">
            <Plus className="h-12 w-12 text-blue-600" />
          </div>
          <h3 className="text-2xl font-bold text-gray-900 mb-3">No trips yet</h3>
          <p className="text-gray-600 mb-8 max-w-md mx-auto">
            Start planning your next adventure! Click the button above to create your first trip.
          </p>
        </div>
      )}
    </div>
  );
};

export default TripListing;
import { useEffect, useState } from "react";
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
  Clock,
  Star,
  TrendingUp,
  Plane,
  Loader2,
  AlertCircle,
  Edit2,
  Trash2,
  Eye
} from "lucide-react";

const TripListingPage = () => {
  const [groups, setGroups] = useState({
    ongoing: [],
    upcoming: [],
    completed: []
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [viewMode, setViewMode] = useState('grid');
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');

  const filters = [
    { id: 'all', label: 'All Trips', count: groups.ongoing.length + groups.upcoming.length + groups.completed.length },
    { id: 'ongoing', label: 'Ongoing', count: groups.ongoing.length },
    { id: 'upcoming', label: 'Upcoming', count: groups.upcoming.length },
    { id: 'completed', label: 'Completed', count: groups.completed.length },
    { id: 'international', label: 'International', count: 0 },
    { id: 'family', label: 'Family', count: 0 }
  ];

  useEffect(() => {
    fetchTrips();
  }, []);

  const fetchTrips = () => {
    setLoading(true);
    setError(null);
    fetch("http://localhost:5000/api/trips")
      .then(res => {
        if (!res.ok) throw new Error('Failed to fetch trips');
        return res.json();
      })
      .then(data => classify(data))
      .catch(err => {
        console.error('Error fetching trips:', err);
        setError(err.message);
        // Fallback to sample data if API fails
        classify([]);
      })
      .finally(() => setLoading(false));
  };

  const classify = (trips) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const result = {
      ongoing: [],
      upcoming: [],
      completed: []
    };

    // Use sample data if API returns empty
    const dataToUse = trips.length > 0 ? trips : getSampleData();

    dataToUse.forEach(trip => {
      if (!trip.startDate || !trip.endDate) return;
      
      const [sy, sm, sd] = trip.startDate.split("-").map(Number);
      const [ey, em, ed] = trip.endDate.split("-").map(Number);

      const start = new Date(sy, sm - 1, sd);
      const end = new Date(ey, em - 1, ed);

      if (start <= today && end >= today) {
        result.ongoing.push(trip);
      } else if (start > today) {
        result.upcoming.push(trip);
      } else {
        result.completed.push(trip);
      }
    });

    console.log("FINAL CLASSIFICATION:", result);
    setGroups(result);
  };

  const getSampleData = () => {
    return [
      {
        _id: '1',
        tripName: 'Tokyo Adventure',
        startDate: '2026-01-03',
        endDate: '2026-01-10',
        destination: 'Tokyo, Japan',
        travelers: 3,
        budget: 2450,
        description: 'Explore the vibrant culture and delicious cuisine of Tokyo',
        status: 'ongoing',
        image: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80',
        daysLeft: 4,
        highlights: ['Sushi Making', 'Mt. Fuji Tour', 'Akihabara']
      },
      {
        _id: '2',
        tripName: 'Paris Getaway',
        startDate: '2026-01-15',
        endDate: '2026-01-22',
        destination: 'Paris, France',
        travelers: 2,
        budget: 3200,
        description: 'Romantic getaway to the city of love',
        status: 'upcoming',
        image: 'https://images.unsplash.com/photo-1502602897457-92c8ce06ef5d?w=800&q=80',
        daysUntil: 12,
        highlights: ['Eiffel Tower', 'Louvre Museum', 'Seine River Cruise']
      },
      {
        _id: '3',
        tripName: 'Bali Retreat',
        startDate: '2025-12-10',
        endDate: '2025-12-17',
        destination: 'Bali, Indonesia',
        travelers: 4,
        budget: 1800,
        description: 'Relaxing beach vacation with surfing lessons',
        status: 'completed',
        image: 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?w=800&q=80',
        rating: 4.5,
        highlights: ['Beach Villas', 'Temple Tour', 'Surfing']
      },
      {
        _id: '4',
        tripName: 'New York City',
        startDate: '2025-11-20',
        endDate: '2025-11-25',
        destination: 'New York, USA',
        travelers: 1,
        budget: 2100,
        description: 'Business trip with some sightseeing',
        status: 'completed',
        image: 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800&q=80',
        rating: 4.2,
        highlights: ['Broadway Show', 'Central Park', 'Statue of Liberty']
      }
    ];
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return 'Date not set';
    const [year, month, day] = dateStr.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric' 
    });
  };

  const calculateProgress = (trip) => {
    if (!trip.startDate || !trip.endDate) return 0;
    
    const today = new Date();
    const start = new Date(trip.startDate);
    const end = new Date(trip.endDate);
    
    if (today < start) return 0;
    if (today > end) return 100;
    
    const totalDuration = end - start;
    const elapsedDuration = today - start;
    
    return Math.min(Math.round((elapsedDuration / totalDuration) * 100), 100);
  };

  const TripCard = ({ trip, type }) => {
    const progress = calculateProgress(trip);
    
    const getStatusColor = () => {
      switch (type) {
        case 'ongoing': return 'from-green-500 to-emerald-500';
        case 'upcoming': return 'from-blue-500 to-cyan-500';
        case 'completed': return 'from-purple-500 to-pink-500';
        default: return 'from-gray-500 to-gray-600';
      }
    };

    const getStatusText = () => {
      switch (type) {
        case 'ongoing': return 'ONGOING';
        case 'upcoming': return 'UPCOMING';
        case 'completed': return 'COMPLETED';
        default: return '';
      }
    };

    const handleDelete = (tripId) => {
      if (window.confirm('Are you sure you want to delete this trip?')) {
        console.log('Deleting trip:', tripId);
        // Add your delete API call here
      }
    };

    const handleEdit = (tripId) => {
      console.log('Editing trip:', tripId);
      // Add your edit logic here
    };

    return (
      <div className="group relative">
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
          {/* Image Section */}
          <div className="relative h-48 overflow-hidden">
            <img 
              src={trip.image || 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80'}
              alt={trip.tripName}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            
            {/* Status Badge */}
            <div className="absolute top-4 right-4">
              <span className={`px-3 py-1 bg-gradient-to-r ${getStatusColor()} text-white text-xs font-semibold rounded-full`}>
                {getStatusText()}
              </span>
            </div>

            {/* Days Info */}
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
              <div className="flex-1">
                <h3 className="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1">
                  {trip.tripName}
                </h3>
                <p className="text-gray-600 text-sm mt-1">
                  {formatDate(trip.startDate)} - {formatDate(trip.endDate)}
                </p>
              </div>
              <div className="relative group/menu">
                <button className="p-2 hover:bg-gray-100 rounded-lg">
                  <MoreVertical className="h-5 w-5 text-gray-400" />
                </button>
                <div className="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all duration-200 z-10">
                  <button 
                    onClick={() => handleEdit(trip._id)}
                    className="w-full flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50"
                  >
                    <Edit2 className="h-4 w-4 mr-3" />
                    Edit Trip
                  </button>
                  <button 
                    onClick={() => console.log('View trip:', trip._id)}
                    className="w-full flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50"
                  >
                    <Eye className="h-4 w-4 mr-3" />
                    View Details
                  </button>
                  <button 
                    onClick={() => handleDelete(trip._id)}
                    className="w-full flex items-center px-4 py-3 text-red-600 hover:bg-red-50"
                  >
                    <Trash2 className="h-4 w-4 mr-3" />
                    Delete Trip
                  </button>
                </div>
              </div>
            </div>

            {/* Trip Details */}
            <div className="space-y-2 mb-4">
              <div className="flex items-center text-gray-600">
                <MapPin className="h-4 w-4 mr-2 flex-shrink-0" />
                <span className="text-sm line-clamp-1">{trip.destination || 'Destination not specified'}</span>
              </div>
              <div className="flex items-center text-gray-600">
                <Users className="h-4 w-4 mr-2 flex-shrink-0" />
                <span className="text-sm">{trip.travelers || 1} traveler{trip.travelers !== 1 ? 's' : ''}</span>
              </div>
              {trip.budget && (
                <div className="flex items-center text-gray-600">
                  <span className="text-sm font-semibold text-gray-900">${trip.budget.toLocaleString()}</span>
                </div>
              )}
            </div>

            {/* Progress Bar for Ongoing Trips */}
            {type === 'ongoing' && (
              <div className="mb-4">
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-gray-600">Progress</span>
                  <span className="text-xs font-semibold text-gray-900">{progress}%</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    className={`bg-gradient-to-r ${getStatusColor()} h-2 rounded-full transition-all duration-500`}
                    style={{ width: `${progress}%` }}
                  ></div>
                </div>
              </div>
            )}

            {/* Description */}
            {trip.description && (
              <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                {trip.description}
              </p>
            )}

            {/* Highlights */}
            {trip.highlights && Array.isArray(trip.highlights) && trip.highlights.length > 0 && (
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
            )}

            {/* Action Button */}
            <button className="w-full flex items-center justify-center text-blue-600 hover:text-blue-700 font-medium py-2.5 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors group/btn">
              View Details
              <ChevronRight className="h-4 w-4 ml-1 group-hover/btn:translate-x-1 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    );
  };

  const TripSection = ({ title, subtitle, trips, type, iconColor, showLiveBadge = false }) => {
    const filteredTrips = trips.filter(trip => {
      if (!searchQuery) return true;
      return trip.tripName?.toLowerCase().includes(searchQuery.toLowerCase()) ||
             trip.destination?.toLowerCase().includes(searchQuery.toLowerCase());
    });

    if (filteredTrips.length === 0 && !loading) {
      return null;
    }

    return (
      <section className="mb-10">
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center">
            <div className={`p-2 bg-gradient-to-r ${iconColor} rounded-lg`}>
              <Clock className="h-5 w-5 text-white" />
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
            <span className="text-blue-600 text-sm font-medium">
              {filteredTrips.length} {filteredTrips.length === 1 ? 'trip' : 'trips'}
            </span>
          </div>
        </div>
        
        {viewMode === 'grid' ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredTrips.map(trip => (
              <TripCard key={trip._id} trip={trip} type={type} />
            ))}
          </div>
        ) : (
          <div className="space-y-4">
            {filteredTrips.map(trip => (
              <div key={trip._id} className="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div className="flex items-center">
                  <img 
                    src={trip.image || 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80'}
                    alt={trip.tripName}
                    className="w-24 h-24 object-cover rounded-lg"
                  />
                  <div className="ml-6 flex-1">
                    <div className="flex justify-between items-start">
                      <div>
                        <h3 className="text-lg font-bold text-gray-900">{trip.tripName}</h3>
                        <div className="flex items-center space-x-4 mt-2">
                          <span className="text-gray-600">{formatDate(trip.startDate)} - {formatDate(trip.endDate)}</span>
                          <span className="text-gray-600">•</span>
                          <span className="text-gray-600">{trip.destination}</span>
                          <span className="text-gray-600">•</span>
                          <span className="text-gray-600">{trip.travelers} travelers</span>
                        </div>
                      </div>
                      <span className={`px-3 py-1 bg-gradient-to-r ${iconColor} text-white text-xs font-semibold rounded-full`}>
                        {type.toUpperCase()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>
    );
  };

  const StatsCard = ({ title, value, icon: Icon, color }) => (
    <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
      <div className="flex items-center">
        <div className={`p-3 ${color} rounded-xl`}>
          <Icon className="h-6 w-6 text-white" />
        </div>
        <div className="ml-4">
          <p className="text-sm text-gray-500">{title}</p>
          <p className="text-2xl font-bold text-gray-900">{value}</p>
        </div>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50/30 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-12 w-12 animate-spin text-blue-600 mx-auto" />
          <p className="mt-4 text-gray-600">Loading your trips from MongoDB...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50/30 flex items-center justify-center">
        <div className="text-center max-w-md">
          <AlertCircle className="h-12 w-12 text-red-500 mx-auto" />
          <h3 className="text-xl font-bold text-gray-900 mt-4">Connection Error</h3>
          <p className="text-gray-600 mt-2 mb-6">
            {error}. Using sample data for demonstration.
          </p>
          <button 
            onClick={fetchTrips}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Retry Connection
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50/30">
      {/* Header */}
      <header className="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-8">
              <div className="flex items-center">
                <Plane className="h-8 w-8 text-blue-600" />
                <h1 className="ml-3 text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                  GlobalTrotter
                </h1>
              </div>
              
              <nav className="hidden md:flex items-center space-x-6">
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Dashboard</a>
                <a href="#" className="text-blue-600 font-semibold border-b-2 border-blue-500 pb-1">Trips</a>
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Explore</a>
                <a href="#" className="text-gray-700 hover:text-blue-600 font-medium">Profile</a>
              </nav>
            </div>
            
            <button className="inline-flex items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2.5 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
              <Plus className="h-5 w-5 mr-2" />
              New Trip
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Page Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Your Travel Adventures</h1>
          <p className="text-gray-600">Trips fetched from MongoDB database</p>
        </div>

        {/* Stats Bar */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <StatsCard
            title="Total Trips"
            value={groups.ongoing.length + groups.upcoming.length + groups.completed.length}
            icon={Plane}
            color="bg-gradient-to-r from-blue-500 to-blue-600"
          />
          <StatsCard
            title="Ongoing"
            value={groups.ongoing.length}
            icon={Clock}
            color="bg-gradient-to-r from-green-500 to-emerald-600"
          />
          <StatsCard
            title="Upcoming"
            value={groups.upcoming.length}
            icon={TrendingUp}
            color="bg-gradient-to-r from-yellow-500 to-orange-600"
          />
          <StatsCard
            title="Completed"
            value={groups.completed.length}
            icon={Star}
            color="bg-gradient-to-r from-purple-500 to-pink-600"
          />
        </div>

        {/* Search and Filter Bar */}
        <div className="mb-10">
          <div className="relative mb-6">
            <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search trips, destinations, or activities..."
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
                  <option>Group by Destination</option>
                </select>
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

        {/* Trip Sections */}
        <div className="space-y-12">
          {activeFilter === 'all' || activeFilter === 'ongoing' ? (
            <TripSection
              title="Ongoing Trips"
              subtitle="Trips you're currently on"
              trips={groups.ongoing}
              type="ongoing"
              iconColor="from-green-500 to-emerald-500"
              showLiveBadge={true}
            />
          ) : null}

          {activeFilter === 'all' || activeFilter === 'upcoming' ? (
            <TripSection
              title="Upcoming Trips"
              subtitle="Your future adventures"
              trips={groups.upcoming}
              type="upcoming"
              iconColor="from-blue-500 to-cyan-500"
            />
          ) : null}

          {activeFilter === 'all' || activeFilter === 'completed' ? (
            <TripSection
              title="Completed Trips"
              subtitle="Your past adventures"
              trips={groups.completed}
              type="completed"
              iconColor="from-purple-500 to-pink-500"
            />
          ) : null}
        </div>

        {/* Empty State */}
        {Object.values(groups).every(arr => arr.length === 0) && !loading && (
          <div className="text-center py-20">
            <div className="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-6">
              <Plane className="h-12 w-12 text-blue-600" />
            </div>
            <h3 className="text-2xl font-bold text-gray-900 mb-3">No trips found</h3>
            <p className="text-gray-600 mb-8 max-w-md mx-auto">
              {error 
                ? 'Could not connect to MongoDB. Please check your connection.'
                : 'Start planning your next adventure! Click the button above to create your first trip.'
              }
            </p>
            <div className="space-x-4">
              <button className="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                <Plus className="h-5 w-5 mr-2" />
                Plan Your First Trip
              </button>
              <button 
                onClick={fetchTrips}
                className="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
              >
                Refresh Data
              </button>
            </div>
          </div>
        )}
      </main>
    </div>
  );
};

export default TripListingPage;

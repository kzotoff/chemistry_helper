var ObjectStorage = function objectStorage(name) {

	var _saveIndex = name || 'objectStorage';
	var _saveInterval = 5000;
	var me;

	if ( ObjectStorage.instances[ _saveIndex ]) {
		me = ObjectStorage.instances[ _saveIndex ];
	} else {
		me = this;
		
		me._saveIndex = _saveIndex;
		me._saveInterval = _saveInterval;
		me._init(_saveIndex);
		ObjectStorage.instances[ _saveIndex ] = me;
	}

	return me;
};

// all instances are stored here
ObjectStorage.instances = {};

ObjectStorage.prototype = {

	_init : function() {
		me = this;
		
		// load previously saved data
		me.local   = JSON.parse( localStorage  .getItem( me._saveIndex+'_local'  ) ) || {};
		me.session = JSON.parse( sessionStorage.getItem( me._saveIndex+'_session') ) || {};

		// start saver
		(function saveOneMoreTime() {
			setTimeout(
				function() {
					me._save();
					saveOneMoreTime();
				},
				me._saveInterval
			);
		})();
	},

	_save : function() {
		localStorage  .setItem(me._saveIndex+'_local'  , JSON.stringify(me.local  ));
		sessionStorage.setItem(me._saveIndex+'_session', JSON.stringify(me.session));
	},

	local   : {},
	session : {}
};